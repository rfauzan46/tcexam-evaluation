<?php
// Include the configuration file
require_once('../config/tce_config.php');

// Define the required page level (assuming K_AUTH_ADMIN_IMPORT is a constant)
$pagelevel = K_AUTH_ADMIN_IMPORT;

// Include the authorization file
require_once('../../shared/code/tce_authorization.php');

// Set the page title
$thispage_title = 'Question Generation Result';

// Include necessary files
require_once('../code/tce_page_header.php');
require_once('../../shared/code/tce_functions_form.php');
require_once('../../shared/code/tce_functions_tcecode.php');
require_once('../../shared/code/tce_functions_auth_sql.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answer_type = $_POST['answer_type'];
    $text = $_POST['text'];

    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        // File is uploaded, handle file upload
        $file = $_FILES['file'];

        // Upload file asynchronously
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'quizai_upload_file.php');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => new CURLFile($file['tmp_name'], $file['type'], $file['name'])]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        if (isset($data['file_path'])) {
            // File uploaded successfully, now send the ask_rag request
            $queryData = [
                'query' => $text,
                'file_path' => $data['file_path'],
                'answer_type' => $answer_type
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'quizai_ask_rag.php');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($queryData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($response, true);

            echo '<div class="center-card"><div class="card" style="background-color:#F6F6F6"><div class="card-body">';
            if (isset($data['response'])) {
                echo '<pre>' . json_encode($data['response'], JSON_PRETTY_PRINT) . '</pre>';
            } else {
                echo '<p>Error: ' . ($data['error'] ?? 'Unknown error') . '</p>';
            }
            echo '</div></div></div>';
        } else {
            echo '<div class="center-card"><div class="card" style="background-color:#F6F6F6"><div class="card-body">';
            echo '<p>Error: ' . ($data['error'] ?? 'File upload failed') . '</p>';
            echo '</div></div></div>';
        }
    } else {
        // No file uploaded, directly send the ask_rag request
        $queryData = [
            'query' => $text,
            'answer_type' => $answer_type
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'quizai_ask_rag.php');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($queryData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        echo '<div class="center-card"><div class="card" style="background-color:#F6F6F6"><div class="card-body">';
        if (isset($data['response'])) {
            echo '<pre>' . json_encode($data['response'], JSON_PRETTY_PRINT) . '</pre>';
        } else {
            echo '<p>Error: ' . ($data['error'] ?? 'Unknown error') . '</p>';
        }
        echo '</div></div></div>';
    }
}


require_once('../code/tce_page_footer.php');
?>
