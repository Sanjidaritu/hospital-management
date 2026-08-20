<?php

require_once 'database.php';

/*
|--------------------------------------------------------------------------
| FILTER DATA
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

$accepting = isset($_GET['accepting']) ? 1 : 0;


/*
|--------------------------------------------------------------------------
| PROVIDER QUERY
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
| KEYWORD SEARCH
|--------------------------------------------------------------------------
*/

if ($keyword !== '') {

    $where[] = "
        (
            p.name LIKE :keyword
            OR p.description LIKE :keyword
            OR p.email LIKE :keyword
            OR p.address LIKE :keyword
            OR p.city LIKE :keyword
            OR p.state LIKE :keyword
            OR p.zip LIKE :keyword
            OR s.name LIKE :keyword
        )
    ";

    $params[':keyword'] = '%' . $keyword . '%';
}


/*
|--------------------------------------------------------------------------
| ZIP CODE
|--------------------------------------------------------------------------
*/

if ($zip !== '') {

    /*
     * Exact ZIP search is NOT used here.
     *
     * We use ZIP as the starting location for
     * distance calculation.
     *
     * Providers will be filtered below using
     * latitude/longitude.
     */

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
| LANGUAGE
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
| INSURANCE
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
| SORTING
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


/*
|--------------------------------------------------------------------------
| EXECUTE
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$providers = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| GET PROVIDER LANGUAGES
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

    $languageStmt->execute([
        $provider['id']
    ]);

    $provider['languages'] =
        $languageStmt->fetchAll(PDO::FETCH_COLUMN);


    /*
    |--------------------------------------------------------------------------
    | DEFAULT LANGUAGE
    |--------------------------------------------------------------------------
    */

    if (empty($provider['languages'])) {

        $provider['languages'] = ['English'];

    }


    /*
    |--------------------------------------------------------------------------
    | DISTANCE
    |--------------------------------------------------------------------------
    */

    $provider['distance'] = null;

}

unset($provider);


/*
|--------------------------------------------------------------------------
| ZIP CODE COORDINATES
|--------------------------------------------------------------------------
|
| For distance calculation, we use the US ZIP code
| latitude/longitude service.
|
|--------------------------------------------------------------------------
*/

$userLatitude = null;
$userLongitude = null;


if ($zip !== '') {

    $zip = preg_replace('/[^0-9]/', '', $zip);

    if (strlen($zip) === 5) {

        $zipUrl =
            'https://api.zippopotam.us/us/' .
            urlencode($zip);

        $context = stream_context_create([
            'http' => [
                'timeout' => 5
            ]
        ]);

        $zipResponse = @file_get_contents(
            $zipUrl,
            false,
            $context
        );

        if ($zipResponse !== false) {

            $zipData = json_decode(
                $zipResponse,
                true
            );

            if (
                isset($zipData['places'][0]['latitude']) &&
                isset($zipData['places'][0]['longitude'])
            ) {

                $userLatitude =
                    (float)$zipData['places'][0]['latitude'];

                $userLongitude =
                    (float)$zipData['places'][0]['longitude'];
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| DISTANCE CALCULATION
|--------------------------------------------------------------------------
*/

function calculateDistance(
    $lat1,
    $lon1,
    $lat2,
    $lon2
) {

    if (
        $lat1 === null ||
        $lon1 === null ||
        $lat2 === null ||
        $lon2 === null
    ) {

        return null;
    }


    $earthRadius = 3958.8;


    $lat1 = deg2rad($lat1);
    $lon1 = deg2rad($lon1);

    $lat2 = deg2rad($lat2);
    $lon2 = deg2rad($lon2);


    $latDifference = $lat2 - $lat1;
    $lonDifference = $lon2 - $lon1;


    $a =
        sin($latDifference / 2) *
        sin($latDifference / 2)
        +
        cos($lat1) *
        cos($lat2) *
        sin($lonDifference / 2) *
        sin($lonDifference / 2);


    $c = 2 * atan2(
        sqrt($a),
        sqrt(1 - $a)
    );


    return $earthRadius * $c;
}


/*
|--------------------------------------------------------------------------
| CALCULATE PROVIDER DISTANCE
|--------------------------------------------------------------------------
*/

if (
    $userLatitude !== null &&
    $userLongitude !== null
) {

    foreach ($providers as &$provider) {

        if (
            isset($provider['latitude']) &&
            isset($provider['longitude']) &&
            $provider['latitude'] !== null &&
            $provider['longitude'] !== null &&
            $provider['latitude'] !== '' &&
            $provider['longitude'] !== ''
        ) {

            $provider['distance'] =
                calculateDistance(
                    $userLatitude,
                    $userLongitude,
                    (float)$provider['latitude'],
                    (float)$provider['longitude']
                );
        }

    }

    unset($provider);


    /*
    |--------------------------------------------------------------------------
    | DISTANCE FILTER
    |--------------------------------------------------------------------------
    */

    if (
        $distance !== '' &&
        is_numeric($distance)
    ) {

        $maxDistance = (float)$distance;

        $providers = array_filter(
            $providers,
            function ($provider) use ($maxDistance) {

                return
                    $provider['distance'] !== null &&
                    $provider['distance'] <= $maxDistance;
            }
        );

        $providers = array_values($providers);
    }


    /*
    |--------------------------------------------------------------------------
    | DISTANCE SORT
    |--------------------------------------------------------------------------
    */

    if ($sort === 'distance') {

        usort(
            $providers,
            function ($a, $b) {

                if ($a['distance'] === null) {
                    return 1;
                }

                if ($b['distance'] === null) {
                    return -1;
                }

                return
                    $a['distance'] <=> $b['distance'];
            }
        );
    }
}

?>
