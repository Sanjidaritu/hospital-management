<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

try {

    $keyword = trim($_GET['keyword'] ?? '');
    $zip = trim($_GET['zip'] ?? '');
    $plan = trim($_GET['plan'] ?? '');
    $language = trim($_GET['language'] ?? '');
    $gender = trim($_GET['gender'] ?? '');
    $specialty = trim($_GET['specialty'] ?? '');
    $area = trim($_GET['area'] ?? '');
    $accepting = $_GET['accepting'] ?? '';
    $sort = $_GET['sort'] ?? 'distance';

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
            s.name AS specialty,
            a.name AS area,

            GROUP_CONCAT(
                DISTINCT l.name
                ORDER BY l.name
                SEPARATOR ', '
            ) AS languages

        FROM providers p

        LEFT JOIN specialties s
            ON p.specialty_id = s.id

        LEFT JOIN areas a
            ON p.area_id = a.id

        LEFT JOIN provider_languages pl
            ON p.id = pl.provider_id

        LEFT JOIN languages l
            ON pl.language_id = l.id

        LEFT JOIN provider_insurance pi
            ON p.id = pi.provider_id

        LEFT JOIN insurance_plans ip
            ON pi.insurance_id = ip.id

        WHERE p.status = 1
    ";

    $params = [];

    /*
     * Keyword search
     */
    if ($keyword !== '') {

        $sql .= "
            AND (
                p.name LIKE :keyword
                OR p.description LIKE :keyword
                OR s.name LIKE :keyword
            )
        ";

        $params[':keyword'] = "%{$keyword}%";
    }

    /*
     * ZIP code
     */
    if ($zip !== '') {

        $sql .= "
            AND p.zip = :zip
        ";

        $params[':zip'] = $zip;
    }

    /*
     * Insurance plan
     */
    if ($plan !== '' && strtolower($plan) !== 'all') {

        $sql .= "
            AND ip.name = :plan
        ";

        $params[':plan'] = $plan;
    }

    /*
     * Language
     */
    if ($language !== '' &&
        strtolower($language) !== 'select' &&
        strtolower($language) !== 'all') {

        $sql .= "
            AND l.name = :language
        ";

        $params[':language'] = $language;
    }

    /*
     * Gender
     */
    if ($gender !== '' &&
        strtolower($gender) !== 'all') {

        $sql .= "
            AND p.gender = :gender
        ";

        $params[':gender'] = $gender;
    }

    /*
     * Specialty
     */
    if ($specialty !== '') {

        $sql .= "
            AND s.name = :specialty
        ";

        $params[':specialty'] = $specialty;
    }

    /*
     * Area
     */
    if ($area !== '' &&
        strtolower($area) !== 'all areas') {

        $sql .= "
            AND a.name = :area
        ";

        $params[':area'] = $area;
    }

    /*
     * Accepting new patients
     */
    if ($accepting === '1') {

        $sql .= "
            AND p.accepting_new_patients = 1
        ";
    }

    $sql .= "
        GROUP BY p.id
    ";

    /*
     * Sorting
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

    echo json_encode([
        "success" => true,
        "count" => count($providers),
        "providers" => $providers
    ]);

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to search providers."
    ]);
}