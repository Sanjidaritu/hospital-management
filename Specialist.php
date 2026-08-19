<?php
require_once 'database.php';

/*
|--------------------------------------------------------------------------
| Load filter data from MySQL
|--------------------------------------------------------------------------
*/

$specialties = $pdo->query("
    SELECT id, name
    FROM specialties
    WHERE status = 1
    ORDER BY name ASC
")->fetchAll();

$languages = $pdo->query("
    SELECT id, name
    FROM languages
    WHERE status = 1
    ORDER BY name ASC
")->fetchAll();

$insurancePlans = $pdo->query("
    SELECT id, name
    FROM insurance_plans
    WHERE status = 1
    ORDER BY name ASC
")->fetchAll();

$areas = $pdo->query("
    SELECT id, name
    FROM areas
    WHERE status = 1
    ORDER BY name ASC
")->fetchAll();


/*
|--------------------------------------------------------------------------
| Search values
|--------------------------------------------------------------------------
*/

$keyword = trim($_GET['keyword'] ?? '');
$zip = trim($_GET['zip'] ?? '');
$gender = trim($_GET['gender'] ?? '');
$specialty = trim($_GET['specialty'] ?? '');
$language = trim($_GET['language'] ?? '');
$insurance = trim($_GET['insurance'] ?? '');
$area = trim($_GET['area'] ?? '');
$accepting = isset($_GET['accepting']) ? 1 : 0;
$sort = trim($_GET['sort'] ?? 'distance');


/*
|--------------------------------------------------------------------------
| Provider query
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
| Keyword
|--------------------------------------------------------------------------
*/

if ($keyword !== '') {

    $where[] = "
        (
            p.name LIKE :keyword
            OR p.description LIKE :keyword
            OR p.email LIKE :keyword
        )
    ";

    $params[':keyword'] = '%' . $keyword . '%';
}


/*
|--------------------------------------------------------------------------
| ZIP
|--------------------------------------------------------------------------
*/

if ($zip !== '') {

    $where[] = "p.zip = :zip";

    $params[':zip'] = $zip;
}


/*
|--------------------------------------------------------------------------
| Gender
|--------------------------------------------------------------------------
*/

if ($gender !== '' && $gender !== 'All') {

    $where[] = "p.gender = :gender";

    $params[':gender'] = $gender;
}


/*
|--------------------------------------------------------------------------
| Specialty
|--------------------------------------------------------------------------
*/

if ($specialty !== '') {

    $where[] = "p.specialty_id = :specialty";

    $params[':specialty'] = $specialty;
}


/*
|--------------------------------------------------------------------------
| Area
|--------------------------------------------------------------------------
*/

if ($area !== '') {

    $where[] = "p.area_id = :area";

    $params[':area'] = $area;
}


/*
|--------------------------------------------------------------------------
| Accepting new patients
|--------------------------------------------------------------------------
*/

if ($accepting) {

    $where[] = "p.accepting_new_patients = 1";
}


/*
|--------------------------------------------------------------------------
| Language filter
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
| Insurance filter
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
| WHERE
|--------------------------------------------------------------------------
*/

$sql .= " WHERE " . implode(" AND ", $where);


/*
|--------------------------------------------------------------------------
| Sorting
|--------------------------------------------------------------------------
*/

switch ($sort) {

    case 'name_asc':
        $sql .= " ORDER BY p.name ASC";
        break;

    case 'name_desc':
        $sql .= " ORDER BY p.name DESC";
        break;

    default:
        $sql .= " ORDER BY p.id DESC";
        break;
}


$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$providers = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Get languages for each provider
|--------------------------------------------------------------------------
*/

foreach ($providers as &$provider) {

    $languageStmt = $pdo->prepare("
        SELECT l.name
        FROM languages l
        INNER JOIN provider_languages pl
            ON l.id = pl.language_id
        WHERE pl.provider_id = ?
        ORDER BY l.name
    ");

    $languageStmt->execute([$provider['id']]);

    $provider['languages'] = $languageStmt->fetchAll(
        PDO::FETCH_COLUMN
    );
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

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Roboto,sans-serif;
}

body{
    background:#f5f5f5;
    color:#333;
}

/* Top Header */

.top-header{
    background:#fff;
    height:55px;
    border-bottom:1px solid #ddd;
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 30px;
}

.logo{
    font-size:30px;
    font-weight:bold;
}

.logo span{
    color:#6c1d74;
}

.user{
    font-size:14px;
}

/* Navbar */

.navbar{
    background:#2d2d2d;
}

.navbar ul{
    list-style:none;
    display:flex;
}

.navbar li{
    position:relative;
}

.navbar a{
    display:block;
    color:#fff;
    text-decoration:none;
    padding:16px 22px;
    font-size:14px;
}

.navbar a:hover{
    background:#6c1d74;
}

/* Container */

.container{
    width:95%;
    margin:auto;
}

/* Breadcrumb */

.breadcrumb{
    margin:25px 0 10px;
    font-size:13px;
    color:#777;
}

.breadcrumb a{
    color:#6c1d74;
    text-decoration:none;
}

/* Heading */

.page-title{
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.page-title h1{
    font-size:42px;
    font-weight:400;
}

.new-search{
    border:2px solid #6c1d74;
    color:#6c1d74;
    background:#fff;
    padding:12px 22px;
    border-radius:4px;
    cursor:pointer;
    font-weight:600;
}

.description{
    margin:15px 0 25px;
    font-size:15px;
}

/* Search Box */

.search-box{
    background:#fff;
    border:1px solid #ddd;
    padding:20px;
    margin-bottom:25px;
}

.search-box h3{
    margin-bottom:20px;
}

.row{
    display:grid;
    grid-template-columns:repeat(6,1fr);
    gap:15px;
    margin-bottom:15px;
}

.form-group{
    display:flex;
    flex-direction:column;
}

label{
    font-size:13px;
    margin-bottom:6px;
}

input,
select{
    height:42px;
    border:1px solid #ccc;
    padding:10px;
    font-size:14px;
    background:#fff;
}

.checkbox{
    display:flex;
    align-items:center;
    margin-top:28px;
}

.checkbox input{
    margin-right:8px;
}

.search-btn{
    margin-top:25px;
    background:#6c1d74;
    color:#fff;
    border:none;
    padding:12px 35px;
    border-radius:4px;
    cursor:pointer;
    font-size:15px;
}

.download{
    background:#6c1d74;
    color:#fff;
    padding:10px 20px;
    border:none;
    border-radius:4px;
    margin-bottom:20px;
    cursor:pointer;
}

/* Provider Card */

.provider-card{
    background:#fff;
    border:1px solid #d7d7d7;
    display:grid;
    grid-template-columns:1.5fr 1.2fr 1fr 1fr 1fr;
    gap:20px;
    padding:20px;
    margin-bottom:18px;
}

.provider-card h2{
    font-size:24px;
    margin-bottom:8px;
}

.provider-card h4{
    color:#6c1d74;
    margin-bottom:8px;
}

.provider-card p{
    margin:4px 0;
    font-size:14px;
}

.phone a{
    color:#6c1d74;
    text-decoration:none;
}

.badge{
    display:inline-block;
    background:#eaf7ea;
    color:#008000;
    padding:4px 8px;
    border-radius:3px;
    font-size:12px;
    margin-top:8px;
}

.no-results{
    background:#fff;
    border:1px solid #ddd;
    padding:40px;
    text-align:center;
    margin-bottom:30px;
}

.no-results h2{
    color:#6c1d74;
    margin-bottom:10px;
}

/* Footer */

footer{
    background:#222;
    color:#fff;
    padding:40px 0;
    margin-top:50px;
}

.footer-grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:30px;
}

footer h3{
    margin-bottom:15px;
}

footer ul{
    list-style:none;
}

footer li{
    margin:8px 0;
}

footer a{
    color:#ddd;
    text-decoration:none;
}

.social i{
    font-size:24px;
    margin-right:15px;
}

/* Responsive */

@media(max-width:1100px){

.row{
    grid-template-columns:repeat(2,1fr);
}

.provider-card{
    grid-template-columns:1fr;
}

.footer-grid{
    grid-template-columns:1fr 1fr;
}

}

@media(max-width:700px){

.navbar ul{
    flex-direction:column;
}

.page-title{
    flex-direction:column;
    align-items:flex-start;
    gap:20px;
}

.row{
    grid-template-columns:1fr;
}

.footer-grid{
    grid-template-columns:1fr;
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

<li><a href="index.html">Home</a></li>

<li><a href="#">My Benefit Plans</a></li>

<li><a href="#">My Services</a></li>

<li><a href="#">My Rewards</a></li>

<li><a href="#">My Payments</a></li>

<li><a href="findcare.html">Find Care</a></li>

<li><a href="#">Quick Resources</a></li>

<li><a href="#">Support</a></li>

<li><a href="#">More</a></li>

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


<!-- SEARCH FORM -->

<form
method="GET"
action="Specialist.php"
class="search-box">


<h3>
Standard Search
</h3>


<div class="row">


<!-- Keywords -->

<div class="form-group">

<label>
Keywords
</label>

<input
type="text"
name="keyword"
value="<?= htmlspecialchars($keyword) ?>"
id="keyword"
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
value="<?= htmlspecialchars($zip ?: '10001') ?>">

</div>


<!-- Distance -->

<div class="form-group">

<label>
Distance
</label>

<select name="distance">

<option>Select</option>

<option value="1.5">
Within 1.5 Miles of
</option>

<option value="5">
Within 5 Miles of
</option>

<option value="10">
Within 10 Miles of
</option>

<option value="20">
Within 20 Miles of
</option>

</select>

</div>


<!-- Insurance -->

<div class="form-group">

<label>
Plan Type
</label>

<select name="insurance">

<option value="">
All Insurance Types
</option>

<?php foreach($insurancePlans as $plan): ?>

<option
value="<?= $plan['id'] ?>"
<?= ($insurance == $plan['id']) ? 'selected' : '' ?>>

<?= htmlspecialchars($plan['name']) ?>

</option>

<?php endforeach; ?>

</select>

</div>


<!-- Language -->

<div class="form-group">

<label>
Language
</label>

<select name="language">

<option value="">
Any Languages (English)
</option>

<?php foreach($languages as $lang): ?>

<option
value="<?= $lang['id'] ?>"
<?= ($language == $lang['id']) ? 'selected' : '' ?>>

<?= htmlspecialchars($lang['name']) ?>

</option>

<?php endforeach; ?>

</select>

</div>


<!-- Sort -->

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


<!-- Gender -->

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

<?php foreach($specialties as $spec): ?>

<option
value="<?= $spec['id'] ?>"
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

<?php foreach($areas as $areaItem): ?>

<option
value="<?= $areaItem['id'] ?>"
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


<!-- RESULTS -->

<?php if(count($providers) === 0): ?>

<div class="no-results">

<h2>
No Providers Found
</h2>

<p>
No providers match your current search criteria.
</p>

</div>

<?php endif; ?>


<?php foreach($providers as $provider): ?>


<div class="provider-card">


<!-- PROVIDER -->

<div>

<h2>

<?= htmlspecialchars($provider['name']) ?>

</h2>


<h4>

<?= htmlspecialchars(
    $provider['specialty_name']
    ?: $provider['provider_type']
) ?>

</h4>


<p>

<?= htmlspecialchars(
    $provider['description'] ?: 'Provider information available.'
) ?>

</p>


<?php if($provider['accepting_new_patients']): ?>

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


<?php if($provider['address']): ?>

<p>
<?= htmlspecialchars($provider['address']) ?>
</p>

<?php endif; ?>


<p>

<?= htmlspecialchars($provider['city']) ?>,

<?= htmlspecialchars($provider['state']) ?>

<?= htmlspecialchars($provider['zip']) ?>

</p>


<br>


<?php if($provider['phone']): ?>

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


<?php if(count($provider['languages']) > 0): ?>

<?php foreach($provider['languages'] as $providerLanguage): ?>

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

<p>
Distance unavailable
</p>

</div>


<!-- ACTIONS -->

<div>

<h4>
Actions
</h4>


<p>

<a
href="tel:<?= htmlspecialchars($provider['phone']) ?>">

Call Provider

</a>

</p>


<?php if($provider['latitude'] && $provider['longitude']): ?>

<p>

<a
target="_blank"
href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($provider['latitude'] . ',' . $provider['longitude']) ?>">

Directions

</a>

</p>

<?php elseif($provider['address']): ?>

<p>

<a
target="_blank"
href="https://www.google.com/maps/search/?api=1&query=<?= urlencode(
    $provider['address'] . ', ' .
    $provider['city'] . ', ' .
    $provider['state'] . ' ' .
    $provider['zip']
) ?>">

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
