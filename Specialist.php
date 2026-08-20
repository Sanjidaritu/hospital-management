<?php

require_once __DIR__ . '/database.php';

/*
|--------------------------------------------------------------------------
| LOAD FILTER DATA
|--------------------------------------------------------------------------
*/

$specialties = $pdo->query("
    SELECT id, name
    FROM specialties
    WHERE status = 1
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$languages = $pdo->query("
    SELECT id, name
    FROM languages
    WHERE status = 1
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$insurancePlans = $pdo->query("
    SELECT id, name
    FROM insurance_plans
    WHERE status = 1
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$areas = $pdo->query("
    SELECT id, name
    FROM areas
    WHERE status = 1
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| SEARCH VALUES
|--------------------------------------------------------------------------
*/

$keyword   = trim($_GET['keyword'] ?? '');
$zip       = trim($_GET['zip'] ?? '');
$distance  = trim($_GET['distance'] ?? '');
$gender    = trim($_GET['gender'] ?? '');
$specialty = trim($_GET['specialty'] ?? '');
$language  = trim($_GET['language'] ?? '');
$insurance = trim($_GET['insurance'] ?? '');
$area      = trim($_GET['area'] ?? '');
$sort      = trim($_GET['sort'] ?? 'distance');

$accepting = isset($_GET['accepting']) && $_GET['accepting'] == '1';


/*
|--------------------------------------------------------------------------
| BASE QUERY
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT DISTINCT
        p.*,
        s.name AS specialty_name,
        a.name AS area_name
    FROM providers p

    LEFT JOIN specialties s
        ON p.specialty_id = s.id

    LEFT JOIN areas a
        ON p.area_id = a.id
";


$params = [];

$where = [
    "p.status = 1"
];


/*
|--------------------------------------------------------------------------
| LANGUAGE JOIN
|--------------------------------------------------------------------------
*/

if ($language !== '') {

    $sql .= "
        INNER JOIN provider_languages pl
            ON p.id = pl.provider_id
    ";

    $where[] = "pl.language_id = :language";

    $params[':language'] = $language;
}


/*
|--------------------------------------------------------------------------
| INSURANCE JOIN
|--------------------------------------------------------------------------
*/

if ($insurance !== '') {

    $sql .= "
        INNER JOIN provider_insurance pi
            ON p.id = pi.provider_id
    ";

    $where[] = "pi.insurance_id = :insurance";

    $params[':insurance'] = $insurance;
}


/*
|--------------------------------------------------------------------------
| KEYWORD SEARCH
|--------------------------------------------------------------------------
|
| Search provider name, description, email,
| phone, address, city, state, ZIP,
| specialty and area.
|--------------------------------------------------------------------------
*/

if ($keyword !== '') {

    $where[] = "
        (
            p.name LIKE :keyword
            OR p.description LIKE :keyword
            OR p.email LIKE :keyword
            OR p.phone LIKE :keyword
            OR p.address LIKE :keyword
            OR p.city LIKE :keyword
            OR p.state LIKE :keyword
            OR p.zip LIKE :keyword
            OR s.name LIKE :keyword
            OR a.name LIKE :keyword
        )
    ";

    $params[':keyword'] = '%' . $keyword . '%';
}


/*
|--------------------------------------------------------------------------
| ZIP CODE
|--------------------------------------------------------------------------
|
| Only filter ZIP when user actually enters a ZIP.
|--------------------------------------------------------------------------
*/

if ($zip !== '') {

    $where[] = "p.zip LIKE :zip";

    $params[':zip'] = $zip . '%';
}


/*
|--------------------------------------------------------------------------
| GENDER
|--------------------------------------------------------------------------
*/

if ($gender !== '' && $gender !== 'All') {

    $where[] = "p.gender = :gender";

    $params[':gender'] = $gender;
}


/*
|--------------------------------------------------------------------------
| SPECIALTY
|--------------------------------------------------------------------------
*/

if ($specialty !== '') {

    $where[] = "p.specialty_id = :specialty";

    $params[':specialty'] = $specialty;
}


/*
|--------------------------------------------------------------------------
| AREA
|--------------------------------------------------------------------------
*/

if ($area !== '') {

    $where[] = "p.area_id = :area";

    $params[':area'] = $area;
}


/*
|--------------------------------------------------------------------------
| ACCEPTING NEW PATIENTS
|--------------------------------------------------------------------------
*/

if ($accepting) {

    $where[] = "p.accepting_new_patients = 1";
}


/*
|--------------------------------------------------------------------------
| WHERE
|--------------------------------------------------------------------------
*/

$sql .= "
    WHERE " . implode(" AND ", $where);


/*
|--------------------------------------------------------------------------
| SORTING
|--------------------------------------------------------------------------
*/

switch ($sort) {

    case 'name_asc':

        $sql .= "
            ORDER BY p.name ASC
        ";

        break;


    case 'name_desc':

        $sql .= "
            ORDER BY p.name DESC
        ";

        break;


    case 'distance':

        /*
         * At this stage we don't have the user's
         * latitude/longitude, so use ZIP first
         * and provider ID as fallback.
         */

        if ($zip !== '') {

            $sql .= "
                ORDER BY
                    CASE
                        WHEN p.zip = :sort_zip THEN 0
                        ELSE 1
                    END,
                    p.id DESC
            ";

            $params[':sort_zip'] = $zip;

        } else {

            $sql .= "
                ORDER BY p.id DESC
            ";
        }

        break;


    default:

        $sql .= "
            ORDER BY p.id DESC
        ";

        break;
}


/*
|--------------------------------------------------------------------------
| EXECUTE QUERY
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare($sql);

    $stmt->execute($params);

    $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    die(
        "<div style='
            padding:20px;
            background:#ffe6e6;
            color:#900;
            font-family:Arial;
        '>
        <strong>Database Search Error:</strong><br>" .
        htmlspecialchars($e->getMessage()) .
        "</div>"
    );
}


/*
|--------------------------------------------------------------------------
| GET LANGUAGES FOR EACH PROVIDER
|--------------------------------------------------------------------------
*/

foreach ($providers as &$provider) {

    try {

        $languageStmt = $pdo->prepare("
            SELECT l.name
            FROM languages l

            INNER JOIN provider_languages pl
                ON l.id = pl.language_id

            WHERE pl.provider_id = ?

            ORDER BY l.name ASC
        ");

        $languageStmt->execute([
            $provider['id']
        ]);

        $provider['languages'] =
            $languageStmt->fetchAll(PDO::FETCH_COLUMN);

    } catch (PDOException $e) {

        $provider['languages'] = [];
    }
}

unset($provider);

?>
<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Find Care Results - Specialists</title>

<link
href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap"
rel="stylesheet">

<link
rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">


<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Roboto, sans-serif;
}

body {
    background: #f5f5f5;
    color: #333;
}


/* HEADER */

.top-header {
    background: #fff;
    height: 55px;
    border-bottom: 1px solid #ddd;

    display: flex;
    align-items: center;
    justify-content: space-between;

    padding: 0 30px;
}

.logo {
    font-size: 30px;
    font-weight: bold;
}

.logo span {
    color: #6c1d74;
}

.user {
    font-size: 14px;
}


/* NAVBAR */

.navbar {
    background: #2d2d2d;
}

.navbar ul {
    list-style: none;
    display: flex;
}

.navbar li {
    position: relative;
}

.navbar a {
    display: block;
    color: #fff;
    text-decoration: none;

    padding: 16px 22px;

    font-size: 14px;
}

.navbar a:hover {
    background: #6c1d74;
}


/* CONTAINER */

.container {
    width: 95%;
    max-width: 1500px;
    margin: auto;
}


/* BREADCRUMB */

.breadcrumb {
    margin: 25px 0 10px;

    font-size: 13px;
    color: #777;
}

.breadcrumb a {
    color: #6c1d74;
    text-decoration: none;
}


/* PAGE TITLE */

.page-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.page-title h1 {
    font-size: 42px;
    font-weight: 400;
}

.new-search {
    border: 2px solid #6c1d74;

    color: #6c1d74;
    background: #fff;

    padding: 12px 22px;

    border-radius: 4px;

    cursor: pointer;

    font-weight: 600;
}

.description {
    margin: 15px 0 25px;

    font-size: 15px;
}


/* SEARCH BOX */

.search-box {
    background: #fff;

    border: 1px solid #ddd;

    padding: 20px;

    margin-bottom: 25px;
}

.search-box h3 {
    margin-bottom: 20px;
}

.row {
    display: grid;

    grid-template-columns:
        repeat(6, 1fr);

    gap: 15px;

    margin-bottom: 15px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

label {
    font-size: 13px;

    margin-bottom: 6px;
}

input,
select {

    height: 42px;

    border: 1px solid #ccc;

    padding: 10px;

    font-size: 14px;

    background: #fff;

    width: 100%;
}

input:focus,
select:focus {

    outline: none;

    border-color: #6c1d74;

    box-shadow:
        0 0 0 2px rgba(108, 29, 116, .1);
}


/* CHECKBOX */

.checkbox {

    display: flex;

    align-items: center;

    margin-top: 28px;
}

.checkbox input {

    width: auto;

    height: auto;

    margin-right: 8px;
}


/* SEARCH BUTTON */

.search-btn {

    margin-top: 25px;

    background: #6c1d74;

    color: #fff;

    border: none;

    padding: 12px 35px;

    border-radius: 4px;

    cursor: pointer;

    font-size: 15px;
}

.search-btn:hover {

    background: #54155b;
}


/* DOWNLOAD */

.download {

    background: #6c1d74;

    color: #fff;

    padding: 10px 20px;

    border: none;

    border-radius: 4px;

    margin-bottom: 20px;

    cursor: pointer;
}


/* RESULT COUNT */

.result-count {

    margin-bottom: 15px;

    font-size: 15px;

    font-weight: 500;
}


/* PROVIDER CARD */

.provider-card {

    background: #fff;

    border: 1px solid #d7d7d7;

    display: grid;

    grid-template-columns:
        1.5fr
        1.2fr
        1fr
        1fr
        1fr;

    gap: 20px;

    padding: 20px;

    margin-bottom: 18px;
}

.provider-card h2 {

    font-size: 24px;

    margin-bottom: 8px;
}

.provider-card h4 {

    color: #6c1d74;

    margin-bottom: 8px;
}

.provider-card p {

    margin: 4px 0;

    font-size: 14px;
}

.phone a {

    color: #6c1d74;

    text-decoration: none;
}

.badge {

    display: inline-block;

    background: #eaf7ea;

    color: #008000;

    padding: 4px 8px;

    border-radius: 3px;

    font-size: 12px;

    margin-top: 8px;
}


/* ACTION LINKS */

.provider-card a {

    color: #6c1d74;

    text-decoration: none;
}

.provider-card a:hover {

    text-decoration: underline;
}


/* NO RESULTS */

.no-results {

    background: #fff;

    border: 1px solid #ddd;

    padding: 40px;

    text-align: center;

    margin-bottom: 30px;
}

.no-results h2 {

    color: #6c1d74;

    margin-bottom: 10px;
}


/* FOOTER */

footer {

    background: #222;

    color: #fff;

    padding: 40px 0;

    margin-top: 50px;
}

.footer-grid {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 30px;
}

footer h3 {

    margin-bottom: 15px;
}

footer ul {

    list-style: none;
}

footer li {

    margin: 8px 0;
}

footer a {

    color: #ddd;

    text-decoration: none;
}

.social i {

    font-size: 24px;

    margin-right: 15px;
}


/* PRINT */

@media print {

    .top-header,
    .navbar,
    .search-box,
    .download,
    .new-search,
    footer {

        display: none !important;
    }

    body {

        background: white;
    }

    .provider-card {

        break-inside: avoid;
    }
}


/* RESPONSIVE */

@media(max-width:1100px) {

    .row {

        grid-template-columns:
            repeat(2, 1fr);
    }

    .provider-card {

        grid-template-columns: 1fr;
    }

    .footer-grid {

        grid-template-columns:
            1fr 1fr;
    }
}


@media(max-width:700px) {

    .navbar ul {

        flex-direction: column;
    }

    .page-title {

        flex-direction: column;

        align-items: flex-start;

        gap: 20px;
    }

    .row {

        grid-template-columns: 1fr;
    }

    .footer-grid {

        grid-template-columns: 1fr;
    }
}

</style>

</head>


<body>


<header class="top-header">

<div class="logo">

MetroPlus<span>Health</span>

</div>

<div class="user">

Mohammad U...

</div>

</header>


<nav class="navbar">

<ul>

<li>
<a href="index.html">Home</a>
</li>

<li>
<a href="#">My Benefit Plans</a>
</li>

<li>
<a href="#">My Services</a>
</li>

<li>
<a href="#">My Rewards</a>
</li>

<li>
<a href="#">My Payments</a>
</li>

<li>
<a href="findcare.html">Find Care</a>
</li>

<li>
<a href="#">Quick Resources</a>
</li>

<li>
<a href="#">Support</a>
</li>

<li>
<a href="#">More</a>
</li>

</ul>

</nav>


<div class="container">


<div class="breadcrumb">

<a href="index.html">Home</a>

>

<a href="findcare.html">Find Care</a>

>

Results

</div>


<div class="page-title">

<h1>

Find Care Results - Specialists

</h1>

<button
type="button"
class="new-search"
onclick="window.location.href='Specialist.php'">

New Search

</button>

</div>


<p class="description">

View Providers and Facilities, get directions, and more.

</p>


<!-- ==========================================================
     SEARCH FORM
=========================================================== -->

<form
method="GET"
action="Specialist.php"
class="search-box">


<h3>

Standard Search

</h3>


<div class="row">


<!-- KEYWORDS -->

<div class="form-group">

<label>

Keywords

</label>

<input
type="text"
name="keyword"
value="<?= htmlspecialchars($keyword) ?>"
placeholder="Ex. Provider Name">

</div>


<!-- ZIP -->

<div class="form-group">

<label>

Zip Code

</label>

<input
type="text"
name="zip"
value="<?= htmlspecialchars($zip) ?>"
placeholder="Enter ZIP Code">

</div>


<!-- DISTANCE -->

<div class="form-group">

<label>

Distance

</label>

<select name="distance">

<option value=""
<?= $distance === '' ? 'selected' : '' ?>>

Select

</option>

<option
value="1.5"
<?= $distance === '1.5' ? 'selected' : '' ?>>

Within 1.5 Miles

</option>

<option
value="5"
<?= $distance === '5' ? 'selected' : '' ?>>

Within 5 Miles

</option>

<option
value="10"
<?= $distance === '10' ? 'selected' : '' ?>>

Within 10 Miles

</option>

<option
value="20"
<?= $distance === '20' ? 'selected' : '' ?>>

Within 20 Miles

</option>

</select>

</div>


<!-- INSURANCE -->

<div class="form-group">

<label>

Plan Type

</label>

<select name="insurance">

<option value="">

All Insurance Types

</option>

<?php foreach ($insurancePlans as $plan): ?>

<option
value="<?= htmlspecialchars($plan['id']) ?>"
<?= ($insurance == $plan['id']) ? 'selected' : '' ?>>

<?= htmlspecialchars($plan['name']) ?>

</option>

<?php endforeach; ?>

</select>

</div>


<!-- LANGUAGE -->

<div class="form-group">

<label>

Language

</label>

<select name="language">

<option value="">

Any Languages (English)

</option>

<?php foreach ($languages as $lang): ?>

<option
value="<?= htmlspecialchars($lang['id']) ?>"
<?= ($language == $lang['id']) ? 'selected' : '' ?>>

<?= htmlspecialchars($lang['name']) ?>

</option>

<?php endforeach; ?>

</select>

</div>


<!-- SORT -->

<div class="form-group">

<label>

Sort

</label>

<select name="sort">

<option
value="distance"
<?= $sort === 'distance' ? 'selected' : '' ?>>

Distance

</option>

<option
value="name_asc"
<?= $sort === 'name_asc' ? 'selected' : '' ?>>

Name (A-Z)

</option>

<option
value="name_desc"
<?= $sort === 'name_desc' ? 'selected' : '' ?>>

Name (Z-A)

</option>

</select>

</div>

</div>


<div class="row">


<!-- GENDER -->

<div class="form-group">

<label>

Gender

</label>

<select name="gender">

<option value="">

All

</option>

<option
value="Male"
<?= $gender === 'Male' ? 'selected' : '' ?>>

Male

</option>

<option
value="Female"
<?= $gender === 'Female' ? 'selected' : '' ?>>

Female

</option>

<option
value="Other"
<?= $gender === 'Other' ? 'selected' : '' ?>>

Other

</option>

</select>

</div>


<!-- SPECIALTY -->

<div class="form-group">

<label>

Specialty

</label>

<select name="specialty">

<option value="">

All Specialties

</option>

<?php foreach ($specialties as $spec): ?>

<option
value="<?= htmlspecialchars($spec['id']) ?>"
<?= ($specialty == $spec['id']) ? 'selected' : '' ?>>

<?= htmlspecialchars($spec['name']) ?>

</option>

<?php endforeach; ?>

</select>

</div>


<!-- AREA -->

<div class="form-group">

<label>

Area

</label>

<select name="area">

<option value="">

All Areas

</option>

<?php foreach ($areas as $areaItem): ?>

<option
value="<?= htmlspecialchars($areaItem['id']) ?>"
<?= ($area == $areaItem['id']) ? 'selected' : '' ?>>

<?= htmlspecialchars($areaItem['name']) ?>

</option>

<?php endforeach; ?>

</select>

</div>


<!-- ACCEPTING -->

<div class="checkbox">

<input
type="checkbox"
name="accepting"
value="1"
<?= $accepting ? 'checked' : '' ?>>

<span>

Accepting New Patients

</span>

</div>


</div>


<button
type="submit"
class="search-btn">

<i class="fa fa-search"></i>

Search

</button>


</form>


<button
class="download"
type="button"
onclick="window.print()">

<i class="fa fa-file-pdf"></i>

Download PDF

</button>


<!-- RESULT COUNT -->

<div class="result-count">

<?= count($providers) ?>

Provider<?= count($providers) == 1 ? '' : 's' ?>

Found

</div>


<!-- ==========================================================
     RESULTS
=========================================================== -->

<?php if (count($providers) === 0): ?>

<div class="no-results">

<h2>

No Providers Found

</h2>

<p>

No providers match your current search criteria.

</p>

</div>

<?php endif; ?>


<?php foreach ($providers as $provider): ?>


<div class="provider-card">


<!-- PROVIDER -->

<div>

<h2>

<?= htmlspecialchars($provider['name']) ?>

</h2>


<h4>

<?= htmlspecialchars(
    $provider['specialty_name']
    ?: ($provider['provider_type'] ?? 'Provider')
) ?>

</h4>


<p>

<?= htmlspecialchars(
    $provider['description']
    ?: 'Provider information available.'
) ?>

</p>


<?php if (!empty($provider['accepting_new_patients'])): ?>

<span class="badge">

Accepting New Patients

</span>

<?php endif; ?>

</div>


<!-- LOCATION -->

<div>

<h4>

Location

</h4>


<?php if (!empty($provider['address'])): ?>

<p>

<?= htmlspecialchars($provider['address']) ?>

</p>

<?php endif; ?>


<p>

<?= htmlspecialchars($provider['city'] ?? '') ?>,

<?= htmlspecialchars($provider['state'] ?? '') ?>

<?= htmlspecialchars($provider['zip'] ?? '') ?>

</p>


<br>


<?php if (!empty($provider['phone'])): ?>

<div class="phone">

<a
href="tel:<?= htmlspecialchars($provider['phone']) ?>">

<?= htmlspecialchars($provider['phone']) ?>

</a>

</div>

<?php endif; ?>

</div>


<!-- LANGUAGES -->

<div>

<h4>

Languages

</h4>


<?php if (!empty($provider['languages'])): ?>

<?php foreach ($provider['languages'] as $providerLanguage): ?>

<p>

<?= htmlspecialchars($providerLanguage) ?>

</p>

<?php endforeach; ?>

<?php else: ?>

<p>

English

</p>

<?php endif; ?>

</div>


<!-- DISTANCE -->

<div>

<h4>

Distance

</h4>


<?php if ($zip !== '' && !empty($provider['zip']) && $provider['zip'] === $zip): ?>

<p>

Matching ZIP

</p>

<?php else: ?>

<p>

Distance unavailable

</p>

<?php endif; ?>

</div>


<!-- ACTIONS -->

<div>

<h4>

Actions

</h4>


<?php if (!empty($provider['phone'])): ?>

<p>

<a
href="tel:<?= htmlspecialchars($provider['phone']) ?>">

Call Provider

</a>

</p>

<?php endif; ?>


<?php

$mapQuery = '';

if (
    !empty($provider['latitude']) &&
    !empty($provider['longitude'])
) {

    $mapQuery =
        $provider['latitude'] .
        ',' .
        $provider['longitude'];

} elseif (!empty($provider['address'])) {

    $mapQuery =
        $provider['address'] .
        ', ' .
        ($provider['city'] ?? '') .
        ', ' .
        ($provider['state'] ?? '') .
        ' ' .
        ($provider['zip'] ?? '');
}

?>


<?php if ($mapQuery !== ''): ?>

<p>

<a
target="_blank"
rel="noopener"
href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($mapQuery) ?>">

Directions

</a>

</p>

<?php endif; ?>

</div>


</div>


<?php endforeach; ?>


</div>


<footer>

<div class="container">

<div class="footer-grid">


<div>

<h3>

MetroPlus Health

</h3>

<p>

Quality healthcare and provider services.

</p>

</div>


<div>

<h3>

Quick Links

</h3>

<ul>

<li>

<a href="index.html">

Home

</a>

</li>

<li>

<a href="findcare.html">

Find Care

</a>

</li>

<li>

<a href="Specialist.php">

Specialists

</a>

</li>

</ul>

</div>


<div>

<h3>

Resources

</h3>

<ul>

<li>

<a href="#">

Member Services

</a>

</li>

<li>

<a href="#">

Support

</a>

</li>

<li>

<a href="#">

Contact Us

</a>

</li>

</ul>

</div>


<div>

<h3>

Follow Us

</h3>

<div class="social">

<i class="fab fa-facebook"></i>

<i class="fab fa-twitter"></i>

<i class="fab fa-linkedin"></i>

</div>

</div>


</div>

</div>

</footer>


</body>

</html>
