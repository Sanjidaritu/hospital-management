<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../database.php';

try {

    $keyword = trim($_GET['keyword'] ?? '');
    $zip = trim($_GET['zip'] ?? '');
    $distance = trim($_GET['distance'] ?? '');
    $plan_type = trim($_GET['plan_type'] ?? '');
    $language = trim($_GET['language'] ?? '');
    $gender = trim($_GET['gender'] ?? '');
    $specialty_id = intval($_GET['specialty_id'] ?? 0);
    $area_id = intval($_GET['area_id'] ?? 0);
    $accepting = isset($_GET['accepting']) && $_GET['accepting'] == '1';
    $sort = trim($_GET['sort'] ?? 'distance');

    $sql = "
        SELECT DISTINCT
            p.id,
            p.name,
            p.provider_type,
            p.description,
            p.gender,
            p.address,
            p.city,
            p.state,
            p.zip,
            p.phone,
            p.email,
            p.accepting_new_patients,
            p.latitude,
            p.longitude,
            s.name AS specialty,
            a.name AS area
        FROM providers p
        LEFT JOIN specialties s
            ON p.specialty_id = s.id
        LEFT JOIN areas a
            ON p.area_id = a.id
        LEFT JOIN provider_languages pl
            ON p.id = pl.provider_id
        LEFT JOIN languages l
            ON pl.language_id = l.id
        WHERE p.status = 1
    ";

    $params = [];

    /* Keyword */

    if ($keyword !== '') {

        $sql .= "
            AND (
                p.name LIKE :keyword
                OR p.description LIKE :keyword
                OR s.name LIKE :keyword
            )
        ";

        $params[':keyword'] = '%' . $keyword . '%';
    }

    /* Zip */

    if ($zip !== '') {

        $sql .= "
            AND p.zip LIKE :zip
        ";

        $params[':zip'] = $zip . '%';
    }

    /* Gender */

    if ($gender !== '' && strtolower($gender) !== 'all') {

        $sql .= "
            AND p.gender = :gender
        ";

        $params[':gender'] = $gender;
    }

    /* Specialty */

    if ($specialty_id > 0) {

        $sql .= "
            AND p.specialty_id = :specialty_id
        ";

        $params[':specialty_id'] = $specialty_id;
    }

    /* Area */

    if ($area_id > 0) {

        $sql .= "
            AND p.area_id = :area_id
        ";

        $params[':area_id'] = $area_id;
    }

    /* Language */

    if ($language !== '' && strtolower($language) !== 'select') {

        $sql .= "
            AND l.name = :language
        ";

        $params[':language'] = $language;
    }

    /* Accepting new patients */

    if ($accepting) {

        $sql .= "
            AND p.accepting_new_patients = 1
        ";
    }

    /* Sorting */

    switch ($sort) {

        case 'name_asc':
            $sql .= " ORDER BY p.name ASC";
            break;

        case 'name_desc':
            $sql .= " ORDER BY p.name DESC";
            break;

        case 'distance':
        default:
            $sql .= " ORDER BY p.id ASC";
            break;
    }

    $stmt = $pdo->prepare($sql);

    $stmt->execute($params);

    $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'count' => count($providers),
        'providers' => $providers
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Database query failed.',
        'error' => $e->getMessage()
    ]);
}
