<?php
/**
 * Created by PhpStorm.
 * User: cardo
 * Date: 10/1/2018
 * Time: 1:51 PM
 */
require_once("../inc/util.inc");
require_once("../inc/user.inc");
require_once("scripts_config.php");
require_once("custom_functions.php");
global $mysqli;

$user = get_logged_in_user();
page_head(tra("Upload Media Files"));
echo "Use the following form to select .FLAC or .WAV files to upload to encode to Opus.</br></br>";
?>
<form method="post" enctype="multipart/form-data">
    <p>
        <input name="MAX_FILE_SIZE" value="268435456" type="hidden"/>
        Browse for a .FLAC or .WAV file to encode to Opus:<br/>
        <input name="files[]" type="file" multiple><br/>
        Choose a Bitrate:<br/>
        <select title="Choose a Bitrate:" name='bitrate'>
            <option value='32'>32</option>
            <option value='64' selected="selected">64</option>
            <option value='96'>96</option>
            <option value='128'>128</option>
            <option value='256'>256</option>
            <option value='320'>320</option>
        </select> kbit/s<br/>
        Category: <input type="text" name="category"><br/><br/>
        <input name="upload" type="submit" value="Upload"/>
    </p>
</form>
<?php
if (isset($_POST['upload'])) {
    for ($i = 0; $i < count($_FILES['files']['name']); $i++) {
        $random_token = bin2hex(random_bytes(16));
        $file_name = $_FILES["files"]["name"][$i];
        $file_tmp = $_FILES["files"]["tmp_name"][$i];
        $ext = pathinfo($file_name, PATHINFO_EXTENSION);
        $filename = $random_token . "." . $ext;
        if (in_array($ext, Array('flac', 'wav'))) {
            if (move_uploaded_file($file_tmp = $_FILES["files"]["tmp_name"][$i], $download_folder . $filename)) {
                chmod($download_folder . $filename, 777);
                chdir($templates_folder);
                $wu_template = fopen($random_token . "_wu", "w");
                fwrite($wu_template, generate_opus_wu_template($filename, $_POST['bitrate'], $random_token));
                fclose($wu_template);
                $result_template = fopen($random_token . "_result", "w");
                fwrite($result_template, generate_opus_result_template($random_token));
                fclose($result_template);
                chdir($root_folder);
                exec(return_job_string("opus_encoder", $random_token, $filename));
                insertAudioTrack($mysqli, $user->id, $random_token . "-out.opus", $_FILES["files"]["name"][$i], "opus_encoder");
                if (isset($_POST['category'])) {
                    insertAlbum($mysqli, $user->id, $_POST['category'], $random_token . "-out.opus");
                }
                rename($download_folder . $filename, $move_folder . $filename);
                echo("Workunit for file " . $_FILES["files"]["name"][$i] . " generated</br>");
            } else {
                echo("File " . $_FILES["files"]["name"][$i] . " failed to upload</br>");
            }
        } else {
            echo("File " . $_FILES["files"]["name"][$i] . " is not a WAV or FLAC file</br>");
        }
    }
}
page_tail();
?>
