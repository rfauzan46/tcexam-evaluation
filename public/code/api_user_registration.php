<?php

//============================================================+
// File name   : api_user_registration.php
// Begin       : 2024-07-23
// Description : User registration API endpoint for TCExam.
//
// Author: OpenAI's ChatGPT
//
// (c) Copyright:
//               Tecnick.com LTD
//               www.tecnick.com
//               info@tecnick.com
//
// License:
//    See LICENSE.TXT file for more information.
//============================================================+

/**
 * @file
 * User registration API endpoint.
 * @package com.tecnick.tcexam.public
 */

require_once('../config/tce_config.php');
require_once('../../shared/config/tce_user_registration.php');
require_once('../../shared/code/tce_authorization.php');
require_once('../../shared/code/tce_functions_otp.php');
require_once('../../shared/code/tce_functions_form.php');

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    // Send the necessary headers and exit
    header('HTTP/1.1 200 OK');
    exit;
}

header('Content-Type: application/json');

// Check if user registration is enabled
if (!K_USRREG_ENABLED) {
    http_response_code(403);
    echo json_encode(['error' => 'User registration is disabled.']);
    exit;
}

// Initialize variables
$user_name = $_POST['user_name'] ?? '';
$user_email = $_POST['user_email'] ?? '';
$newpassword = $_POST['newpassword'] ?? '';
$newpassword_repeat = $_POST['newpassword_repeat'] ?? '';
$user_regnumber = $_POST['user_regnumber'] ?? '';
$user_firstname = $_POST['user_firstname'] ?? '';
$user_lastname = $_POST['user_lastname'] ?? '';
$user_birthdate = $_POST['user_birthdate'] ?? '';
$user_birthplace = $_POST['user_birthplace'] ?? '';
$user_ssn = $_POST['user_ssn'] ?? '';
$user_groups = $_POST['user_groups'] ?? [];

$regfields = [
    'user_name' => 2,
    'newpassword' => 2,
    'newpassword_repeat' => 2,
    'user_email' => 2  ,
    'user_regnumber' => 0,
    'user_firstname' => 0,
    'user_lastname' => 0,
    'user_birthdate' => 0,
    'user_birthplace' => 0,
    'user_ssn' => 0,
    'user_groups' => 0,
    'user_agreement' => 0,
];

$fielddesc = [
    'user_name' => $l['w_name'],
    'newpassword' => $l['w_password'],
    'newpassword_repeat' => $l['w_password'],
    'user_email' => $l['w_email'],
    'user_regnumber' => $l['w_regcode'],
    'user_firstname' => $l['w_firstname'],
    'user_lastname' => $l['w_lastname'],
    'user_birthdate' => $l['w_birth_date'],
    'user_birthplace' => $l['w_birth_place'],
    'user_ssn' => $l['w_fiscal_code'],
    'user_groups' => $l['w_groups'],
    'user_agreement' => $l['w_i_agree'],
];

// Validate required fields
$missing_fields = [];
foreach ($regfields as $field => $required) {
    if ($required == 2 && empty($$field)) {
        $missing_fields[] = $fielddesc[$field];
    }
}

if (!empty($missing_fields)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields', 'fields' => $missing_fields]);
    exit;
}

// Check if passwords match
if ($newpassword !== $newpassword_repeat) {
    http_response_code(400);
    echo json_encode(['error' => 'Passwords do not match.']);
    exit;
}

// Check if username, registration number, and SSN are unique
if (!F_check_unique(K_TABLE_USERS, "user_name='" . F_escape_sql($db, $user_name) . "'")) {
    http_response_code(400);
    echo json_encode(['error' => 'Username already exists.']);
    exit;
}

// if (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
//     // Handle invalid email
//     echo json_encode(['error' => 'Email is invalid']);
//     exit;
// }


if (!empty($user_regnumber) && !F_check_unique(K_TABLE_USERS, "user_regnumber='" . F_escape_sql($db, $user_regnumber) . "'")) {
    http_response_code(400);
    echo json_encode(['error' => 'Registration number already exists.']);
    exit;
}

if (!empty($user_ssn) && !F_check_unique(K_TABLE_USERS, "user_ssn='" . F_escape_sql($db, $user_ssn) . "'")) {
    http_response_code(400);
    echo json_encode(['error' => 'SSN already exists.']);
    exit;
}

// Hash the password
$user_password = getPasswordHash($newpassword);
$user_otpkey = F_getRandomOTPkey();
$user_verifycode = md5(uniqid(random_int(0, mt_getrandmax()), true));
$user_ip = getNormalizedIP($_SERVER['REMOTE_ADDR']);
$user_regdate = date(K_TIMESTAMP_FORMAT);
$usrlevel = 1;

// Insert user into the database
$sql = 'INSERT INTO ' . K_TABLE_USERS . ' (
    user_regdate,
    user_ip,
    user_name,
    user_email,
    user_password,
    user_regnumber,
    user_firstname,
    user_lastname,
    user_birthdate,
    user_birthplace,
    user_ssn,
    user_level,
    user_verifycode,
    user_otpkey
    ) VALUES (
    \'' . F_escape_sql($db, $user_regdate) . '\',
    \'' . F_escape_sql($db, $user_ip) . '\',
    \'' . F_escape_sql($db, $user_name) . '\',
    ' . F_empty_to_null($user_email) . ',
    \'' . F_escape_sql($db, $user_password) . '\',
    ' . F_empty_to_null($user_regnumber) . ',
    ' . F_empty_to_null($user_firstname) . ',
    ' . F_empty_to_null($user_lastname) . ',
    ' . F_empty_to_null($user_birthdate) . ',
    ' . F_empty_to_null($user_birthplace) . ',
    ' . F_empty_to_null($user_ssn) . ',
    \'' . $usrlevel . '\',
    \'' . $user_verifycode . '\',
    ' . F_empty_to_null($user_otpkey) . '
    )';
if (!$r = F_db_query($sql, $db)) {
    F_display_db_error(false);
    http_response_code(500);
    echo json_encode(['error' => 'Database error.']);
    exit;
}

$user_id = F_db_insert_id($db, K_TABLE_USERS, 'user_id');

// Add user's groups
if (empty($user_groups)) {
    $user_groups = [K_USRREG_GROUP];
} elseif (!in_array(K_USRREG_GROUP, $user_groups)) {
    $user_groups[] = K_USRREG_GROUP;
}

foreach ($user_groups as $group_id) {
    $sql = 'INSERT INTO ' . K_TABLE_USERGROUP . ' (
        usrgrp_user_id,
        usrgrp_group_id
        ) VALUES (
        \'' . $user_id . '\',
        \'' . $group_id . '\'
        )';
    if (!$r = F_db_query($sql, $db)) {
        F_display_db_error(false);
        http_response_code(500);
        echo json_encode(['error' => 'Database error.']);
        exit;
    }
}

// if (K_USRREG_EMAIL_CONFIRM) {
//     // Require email confirmation
//     require_once('../../shared/code/tce_functions_user_registration.php');
//     F_send_user_reg_email($user_id, $user_email, $user_verifycode);
//     echo json_encode(['message' => 'Registration successful. Verification email sent.']);
// } else {
//     echo json_encode(['message' => 'Registration successful.']);
// }

echo json_encode(['message' => 'Registration successful.']);

exit;

//============================================================+
// END OF FILE
//============================================================+
