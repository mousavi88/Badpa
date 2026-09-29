<?php
if (isset($_POST['name'])) {
    $fileName = $_POST['name'];
    $filePath = "$fileName.json"; 

    if (!file_exists($filePath)) {
        $file = fopen($filePath, "w");
        if ($file) {
            fwrite($file, "[]");
            fclose($file);
            echo "File was successfully created: " . $fileName;
        } else {
            echo "Error creating file.";
        }
    } else {
        echo "A file with this name already exists.";
    }
} else {
    echo "The 'name' parameter was not sent.";
}
?>
