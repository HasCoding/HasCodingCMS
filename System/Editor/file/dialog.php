<?php
$time = time();

$config = include 'config/config.php';

if (USE_ACCESS_KEYS == true){
	if (!isset($_GET['akey'], $config['access_keys']) || empty($config['access_keys'])){
		die('Access Denied!');
	}

    $_GET['akey'] = strip_tags(preg_replace("/[^a-zA-Z0-9\._-]/", '', $_GET['akey']));

	if (!in_array($_GET['akey'], $config['access_keys'])){
		die('Access Denied!');
	}
}

$_SESSION['RF']["verify"] = "RESPONSIVEfilemanager";

if (isset($_POST['submit'])) {
    include 'upload.php';
} else {
    $available_languages = include 'lang/languages.php';

    list($preferred_language) = array_values(array_filter(array(
        isset($_GET['lang']) ? $_GET['lang'] : null,
        isset($_SESSION['RF']['language']) ? $_SESSION['RF']['language'] : null,
        $config['default_language']
    )));

    if (array_key_exists($preferred_language, $available_languages)) {
        $_SESSION['RF']['language'] = $preferred_language;
    } else {
        $_SESSION['RF']['language'] = $config['default_language'];
    }
}

include 'include/utils.php';

$subdir_path = '';

if (isset($_GET['fldr']) && !empty($_GET['fldr'])) {
    $subdir_path = rawurldecode(trim(strip_tags($_GET['fldr']), "/"));
} elseif (isset($_SESSION['RF']['fldr']) && !empty($_SESSION['RF']['fldr'])) {
    $subdir_path = rawurldecode(trim(strip_tags($_SESSION['RF']['fldr']), "/"));
}

if (checkRelativePath($subdir_path)) {
    $subdir = strip_tags($subdir_path) . "/";
    $_SESSION['RF']['fldr'] = $subdir_path;
    $_SESSION['RF']["filter"] = '';
} else {
    $subdir = '';
}

if ($subdir == "") {
    if (!empty($_COOKIE['last_position']) && strpos($_COOKIE['last_position'], '.') === FALSE) {
        $subdir = trim($_COOKIE['last_position']);
    }
}
//remember last position
setcookie('last_position', $subdir, time() + (86400 * 7));

if ($subdir == "/") { $subdir = ""; }

// If hidden folders are specified
if (count($config['hidden_folders'])) {
    // If hidden folder appears in the path specified in URL parameter "fldr"
    $dirs = explode('/', $subdir);
    foreach ($dirs as $dir) {
        if ($dir !== '' && in_array($dir, $config['hidden_folders'])) {
            // Ignore the path
            $subdir = "";
            break;
        }
    }
}

if ($config['show_total_size']) {
    list($sizeCurrentFolder, $fileCurrentNum, $foldersCurrentCount) = folder_info($config['current_path'], false);
}

/***
 * SUB-DIR CODE
 ***/
if (!isset($_SESSION['RF']["subfolder"])) {
    $_SESSION['RF']["subfolder"] = '';
}
$rfm_subfolder = '';

if (!empty($_SESSION['RF']["subfolder"])
    && strpos($_SESSION['RF']["subfolder"], "/") !== 0
    && strpos($_SESSION['RF']["subfolder"], '.') === FALSE
) {
    $rfm_subfolder = $_SESSION['RF']['subfolder'];
}

if ($rfm_subfolder != "" && $rfm_subfolder[strlen($rfm_subfolder) - 1] != "/") {
    $rfm_subfolder .= "/";
}

$ftp = ftp_con($config);

if (($ftp && !$ftp->isDir($config['ftp_base_folder'] . $config['upload_dir'] . $rfm_subfolder . $subdir)) || (!$ftp && !file_exists($config['current_path'] . $rfm_subfolder . $subdir))) {
    $subdir = '';
    $rfm_subfolder = "";
}


$cur_dir		= $config['upload_dir'].$rfm_subfolder.$subdir;
$cur_dir_thumb	= $config['thumbs_upload_dir'].$rfm_subfolder.$subdir;
$thumbs_path	= $config['thumbs_base_path'].$rfm_subfolder.$subdir;
$parent			= $rfm_subfolder.$subdir;

if ($ftp) {
    $cur_dir = $config['ftp_base_folder'] . $cur_dir;
    $cur_dir_thumb = $config['ftp_base_folder'] . $cur_dir_thumb;
    $thumbs_path = str_replace(array('/..', '..'), '', $cur_dir_thumb);
    $parent = $config['ftp_base_folder'] . $parent;
}

if (!$ftp) {
    $cycle = TRUE;
    $max_cycles = 50;
    $i = 0;
    while ($cycle && $i < $max_cycles) {
        $i++;

        if ($parent == "./") {
            $parent = "";
        }

        if (file_exists($config['current_path'] . $parent . "config.php")) {
            $configTemp = include $config['current_path'] . $parent . 'config.php';
            $config = array_merge($config, $configTemp);
            $cycle = FALSE;
        }

        if ($parent == "") {
            $cycle = FALSE;
        } else {
            $parent = fix_dirname($parent) . "/";
        }
    }

    if (!is_dir($thumbs_path)) {
        create_folder(FALSE, $thumbs_path, $ftp, $config);
    }
}

$multiple = null;

if (isset($_GET['multiple'])) {
    if ($_GET['multiple'] == 1) {
        $multiple = 1;
        $config['multiple_selection'] = true;
        $config['multiple_selection_action_button'] = true;
    } elseif ($_GET['multiple'] == 0) {
        $multiple = 0;
        $config['multiple_selection'] = false;
        $config['multiple_selection_action_button'] = false;
    }
}

if (isset($_GET['callback'])) {
    $callback = strip_tags($_GET['callback']);
    $_SESSION['RF']["callback"] = $callback;
} else {
    $callback = 0;

    if (isset($_SESSION['RF']["callback"])) {
        $callback = $_SESSION['RF']["callback"];
    }
}

$popup = isset($_GET['popup']) ? strip_tags($_GET['popup']) : 0;
//Sanitize popup
$popup = !!$popup;

$crossdomain = isset($_GET['crossdomain']) ? strip_tags($_GET['crossdomain']) : 0;
//Sanitize crossdomain
$crossdomain=!!$crossdomain;

//view type
if(!isset($_SESSION['RF']["view_type"]))
{
    $view = $config['default_view'];
    $_SESSION['RF']["view_type"] = $view;
}

if (isset($_GET['view']))
{
    $view = fix_get_params($_GET['view']);
    $_SESSION['RF']["view_type"] = $view;
}

$view = $_SESSION['RF']["view_type"];

//filter
$filter = "";
if(isset($_SESSION['RF']["filter"]))
{
    $filter = $_SESSION['RF']["filter"];
}

if(isset($_GET["filter"]))
{
    $filter = fix_get_params($_GET["filter"]);
}

if (!isset($_SESSION['RF']['sort_by']))
{
    $_SESSION['RF']['sort_by'] = 'name';
}

if (isset($_GET["sort_by"])) {
    $sort_by = $_SESSION['RF']['sort_by'] = fix_get_params($_GET["sort_by"]);
} else {
    $sort_by = $_SESSION['RF']['sort_by'];
}


if (!isset($_SESSION['RF']['descending'])) {
    $_SESSION['RF']['descending'] = TRUE;
}

if (isset($_GET["descending"])) {
    $descending = $_SESSION['RF']['descending'] = fix_get_params($_GET["descending"]) == 1;
} else {
    $descending = $_SESSION['RF']['descending'];
}

$boolarray = array(false => 'false', true => 'true');

$return_relative_url = isset($_GET['relative_url']) && $_GET['relative_url'] == "1" ? true : false;

if (!isset($_GET['type'])) {
    $_GET['type'] = 0;
}

$extensions = null;
if (isset($_GET['extensions'])) {
    $extensions = json_decode(urldecode($_GET['extensions']));
    $ext_tmp = array();
    foreach ($extensions as $extension) {
        $extension = fix_strtolower($extension);
        if (check_file_extension($extension, $config)) {
            $ext_tmp[] = $extension;
        }
    }
    if ($extensions) {
        $ext = $ext_tmp;
        $config['ext'] = $ext_tmp;
        $config['show_filter_buttons'] = false;
    }
}

if (isset($_GET['editor'])) {
    $editor = strip_tags($_GET['editor']);
} else {
    $editor = $_GET['type'] == 0 ? null : 'tinymce';
}

$field_id = isset($_GET['field_id']) ? fix_get_params($_GET['field_id']) : null;
$type_param = fix_get_params($_GET['type']);
$apply = null;

if ($multiple) {
    $apply = 'apply_multiple';
}

if ($type_param == 1) {
    $apply_type = 'apply_img';
} elseif ($type_param == 2) {
    $apply_type = 'apply_link';
} elseif ($type_param == 0 && !$field_id) {
    $apply_type = 'apply_none';
} elseif ($type_param == 3) {
    $apply_type = 'apply_video';
} else {
    $apply_type = 'apply';
}

if(!$apply){
    $apply = $apply_type;
}

$get_params = array(
    'editor'        => $editor,
    'type'          => $type_param,
    'lang'          => $lang,
    'popup'         => $popup,
    'crossdomain'   => $crossdomain,
    'extensions'    => ($extensions) ? urlencode(json_encode($extensions)) : null ,
    'field_id'      => $field_id,
    'multiple'      => $multiple,
    'relative_url'  => $return_relative_url,
    'akey'          => (isset($_GET['akey']) && $_GET['akey'] != '' ? $_GET['akey'] : 'key')
);
if (isset($_GET['CKEditorFuncNum'])) {
    $get_params['CKEditorFuncNum'] = $_GET['CKEditorFuncNum'];
    $get_params['CKEditor'] = (isset($_GET['CKEditor']) ? $_GET['CKEditor'] : '');
}
$get_params['fldr'] ='';

$get_params = http_build_query($get_params);
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
        <meta name="robots" content="noindex,nofollow">
        <title>HasCoding File Manager</title>
        <link rel="shortcut icon" href="img/ico/favicon.ico">
        <!-- CSS to style the file input field as button and adjust the Bootstrap progress bars -->
        <link rel="stylesheet" href="css/jquery.fileupload.css">
        <link rel="stylesheet" href="css/jquery.fileupload-ui.css">
        <!-- CSS adjustments for browsers with JavaScript disabled -->
        <noscript><link rel="stylesheet" href="css/jquery.fileupload-noscript.css"></noscript>
        <noscript><link rel="stylesheet" href="css/jquery.fileupload-ui-noscript.css"></noscript>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jplayer/2.7.1/skin/blue.monday/jplayer.blue.monday.min.css" />
        <link rel="stylesheet" href="https://uicdn.toast.com/tui-image-editor/latest/tui-image-editor.css">
        <link href="css/style.css?v=<?php echo $version; ?>" rel="stylesheet" type="text/css" />
        <!--[if lt IE 8]>
        <style>
            .img-container span, .img-container-mini span {
                display: inline-block;
                height: 100%;
            }
        </style>
        <![endif]-->

        <script src="https://code.jquery.com/jquery-1.12.4.min.js" integrity="sha256-ZosEbRLbNQzLpnKIkEdrPv7lOy9C27hHQ+Xp8a4MxAQ=" crossorigin="anonymous"></script>
        <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js" integrity="sha256-VazP97ZCwtekAsvgPBSUwPFKdrwD3unUfSGVYrahUqU=" crossorigin="anonymous"></script>
        <script src="js/plugins.js?v=<?php echo $version; ?>"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jplayer/2.9.2/jplayer/jquery.jplayer.min.js"></script>
        <link type="text/css" href="https://uicdn.toast.com/tui-color-picker/v2.2.0/tui-color-picker.css" rel="stylesheet">
        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/1.6.7/fabric.js"></script>
        <script type="text/javascript" src="https://uicdn.toast.com/tui.code-snippet/v1.5.0/tui-code-snippet.min.js"></script>
        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/1.3.3/FileSaver.min.js"></script>
        <script type="text/javascript" src="https://uicdn.toast.com/tui-color-picker/v2.2.0/tui-color-picker.js"></script>
        <script src="https://uicdn.toast.com/tui-image-editor/latest/tui-image-editor.js"></script>
        <script src="js/modernizr.custom.js"></script>

        <!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
        <!--[if lt IE 9]>
        <script src="//cdnjs.cloudflare.com/ajax/libs/html5shiv/3.6.2/html5shiv.js"></script>
        <![endif]-->

        <script type="text/javascript">
            var ext_img=new Array('<?php echo implode("','", $config['ext_img'])?>');
            var image_editor= <?php echo $config['tui_active']?"true":"false";?>;
        </script>

        
        <script src="js/include.js?v=<?php echo $version; ?>"></script>
</head>
<body>
    <!-- The Templates plugin is included to render the upload/download listings -->
    <script src="//blueimp.github.io/JavaScript-Templates/js/tmpl.min.js"></script>
    <!-- The Load Image plugin is included for the preview images and image resizing functionality -->
    <script src="//cdnjs.cloudflare.com/ajax/libs/blueimp-load-image/2.18.0/load-image.all.min.js"></script>
    <!-- The Canvas to Blob plugin is included for image resizing functionality -->
    <script src="//blueimp.github.io/JavaScript-Canvas-to-Blob/js/canvas-to-blob.min.js"></script>
    <!-- The Iframe Transport is required for browsers without support for XHR file uploads -->
    <script src="js/jquery.iframe-transport.js"></script>
    <!-- The basic File Upload plugin -->
    <script src="js/jquery.fileupload.js"></script>
    <!-- The File Upload processing plugin -->
    <script src="js/jquery.fileupload-process.js"></script>
    <!-- The File Upload image preview & resize plugin -->
    <script src="js/jquery.fileupload-image.js"></script>
    <!-- The File Upload audio preview plugin -->
    <script src="js/jquery.fileupload-audio.js"></script>
    <!-- The File Upload video preview plugin -->
    <script src="js/jquery.fileupload-video.js"></script>
    <!-- The File Upload validation plugin -->
    <script src="js/jquery.fileupload-validate.js"></script>
    <!-- The File Upload user interface plugin -->
    <script src="js/jquery.fileupload-ui.js"></script>

    <input type="hidden" id="ftp" value="<?php echo !!$ftp; ?>" />
    <input type="hidden" id="popup" value="<?php echo $popup;?>" />
    <input type="hidden" id="callback" value="<?php echo $callback; ?>" />
    <input type="hidden" id="crossdomain" value="<?php echo $crossdomain;?>" />
    <input type="hidden" id="editor" value="<?php echo $editor;?>" />
    <input type="hidden" id="view" value="<?php echo $view;?>" />
    <input type="hidden" id="subdir" value="<?php echo $subdir;?>" />
    <input type="hidden" id="field_id" value="<?php echo $field_id;?>" />
    <input type="hidden" id="multiple" value="<?php echo $multiple;?>" />
    <input type="hidden" id="type_param" value="<?php echo $type_param;?>" />
    <input type="hidden" id="upload_dir" value="<?php echo $config['upload_dir'];?>" />
    <input type="hidden" id="cur_dir" value="<?php echo $cur_dir;?>" />
    <input type="hidden" id="cur_dir_thumb" value="<?php echo $cur_dir_thumb;?>" />
    <input type="hidden" id="insert_folder_name" value="<?php echo trans('Insert_Folder_Name');?>" />
    <input type="hidden" id="rename_existing_folder" value="<?php echo trans('Rename_existing_folder');?>" />
    <input type="hidden" id="new_folder" value="<?php echo trans('New_Folder');?>" />
    <input type="hidden" id="ok" value="<?php echo trans('OK');?>" />
    <input type="hidden" id="cancel" value="<?php echo trans('Cancel');?>" />
    <input type="hidden" id="rename" value="<?php echo trans('Rename');?>" />
    <input type="hidden" id="lang_duplicate" value="<?php echo trans('Duplicate');?>" />
    <input type="hidden" id="duplicate" value="<?php if($config['duplicate_files']) echo 1; else echo 0;?>" />
    <input type="hidden" id="base_url" value="<?php echo $config['base_url']?>"/>
    <input type="hidden" id="ftp_base_url" value="<?php echo $config['ftp_base_url']?>"/>
    <input type="hidden" id="fldr_value" value="<?php echo $subdir;?>"/>
    <input type="hidden" id="sub_folder" value="<?php echo $rfm_subfolder;?>"/>
    <input type="hidden" id="return_relative_url" value="<?php echo $return_relative_url == true ? 1 : 0;?>"/>
    <input type="hidden" id="file_number_limit_js" value="<?php echo $config['file_number_limit_js'];?>" />
    <input type="hidden" id="sort_by" value="<?php echo $sort_by;?>" />
    <input type="hidden" id="descending" value="<?php echo $descending?1:0;?>" />
    <input type="hidden" id="current_url" value="<?php echo str_replace(array('&filter='.$filter,'&sort_by='.$sort_by,'&descending='.intval($descending)),array(''),$config['base_url'].htmlspecialchars($_SERVER['REQUEST_URI']));?>" />
    <input type="hidden" id="lang_show_url" value="<?php echo trans('Show_url');?>" />
    <input type="hidden" id="copy_cut_files_allowed" value="<?php if($config['copy_cut_files']) echo 1; else echo 0;?>" />
    <input type="hidden" id="copy_cut_dirs_allowed" value="<?php if($config['copy_cut_dirs']) echo 1; else echo 0;?>" />
    <input type="hidden" id="copy_cut_max_size" value="<?php echo $config['copy_cut_max_size'];?>" />
    <input type="hidden" id="copy_cut_max_count" value="<?php echo $config['copy_cut_max_count'];?>" />
    <input type="hidden" id="lang_copy" value="<?php echo trans('Copy');?>" />
    <input type="hidden" id="lang_cut" value="<?php echo trans('Cut');?>" />
    <input type="hidden" id="lang_paste" value="<?php echo trans('Paste');?>" />
    <input type="hidden" id="lang_paste_here" value="<?php echo trans('Paste_Here');?>" />
    <input type="hidden" id="lang_paste_confirm" value="<?php echo trans('Paste_Confirm');?>" />
    <input type="hidden" id="lang_files" value="<?php echo trans('Files');?>" />
    <input type="hidden" id="lang_folders" value="<?php echo trans('Folders');?>" />
    <input type="hidden" id="lang_files_on_clipboard" value="<?php echo trans('Files_ON_Clipboard');?>" />
    <input type="hidden" id="clipboard" value="<?php echo ((isset($_SESSION['RF']['clipboard']['path']) && trim($_SESSION['RF']['clipboard']['path']) != null) ? 1 : 0);?>" />
    <input type="hidden" id="lang_clear_clipboard_confirm" value="<?php echo trans('Clear_Clipboard_Confirm');?>" />
    <input type="hidden" id="lang_file_permission" value="<?php echo trans('File_Permission');?>" />
    <input type="hidden" id="chmod_files_allowed" value="<?php if($config['chmod_files']) echo 1; else echo 0;?>" />
    <input type="hidden" id="chmod_dirs_allowed" value="<?php if($config['chmod_dirs']) echo 1; else echo 0;?>" />
    <input type="hidden" id="lang_lang_change" value="<?php echo trans('Lang_Change');?>" />
    <input type="hidden" id="edit_text_files_allowed" value="<?php if($config['edit_text_files']) echo 1; else echo 0;?>" />
    <input type="hidden" id="lang_edit_file" value="<?php echo trans('Edit_File');?>" />
    <input type="hidden" id="lang_new_file" value="<?php echo trans('New_File');?>" />
    <input type="hidden" id="lang_filename" value="<?php echo trans('Filename');?>" />
    <input type="hidden" id="lang_file_info" value="<?php echo fix_strtoupper(trans('File_info'));?>" />
    <input type="hidden" id="lang_edit_image" value="<?php echo trans('Edit_image');?>" />
    <input type="hidden" id="lang_error_upload" value="<?php echo trans('Error_Upload');?>" />
    <input type="hidden" id="lang_select" value="<?php echo trans('Select');?>" />
    <input type="hidden" id="lang_extract" value="<?php echo trans('Extract');?>" />
    <input type="hidden" id="extract_files" value="<?php if($config['extract_files']) echo 1; else echo 0;?>" />
    <input type="hidden" id="transliteration" value="<?php echo $config['transliteration']?"true":"false";?>" />
    <input type="hidden" id="convert_spaces" value="<?php echo $config['convert_spaces']?"true":"false";?>" />
    <input type="hidden" id="replace_with" value="<?php echo $config['convert_spaces']? $config['replace_with'] : "";?>" />
    <input type="hidden" id="lower_case" value="<?php echo $config['lower_case']?"true":"false";?>" />
    <input type="hidden" id="show_folder_size" value="<?php echo $config['show_folder_size'];?>" />
    <input type="hidden" id="add_time_to_img" value="<?php echo $config['add_time_to_img'];?>" />
<?php if($config['upload_files']){ ?>
<!-- uploader div start -->
<div class="uploader">
    <div class="flex">
        <div class="text-center">
            <button class="btn btn-inverse close-uploader"><i class="icon-backward icon-white"></i> <?php echo trans('Return_Files_List')?></button>
        </div>
        <div class="space10"></div>
        <div class="tabbable upload-tabbable"> <!-- Only required for left/right tabs -->
            <div class="container1">
            <ul class="nav nav-tabs">
                <li class="active"><a href="#baseUpload" data-toggle="tab"><?php echo trans('Upload_base');?></a></li>
                <?php if($config['url_upload']){ ?>
                <li><a href="#urlUpload" data-toggle="tab"><?php echo trans('Upload_url');?></a></li>
                <?php } ?>
            </ul>
            <div class="tab-content">
                <div class="tab-pane active" id="baseUpload">
                    <!-- The file upload form used as target for the file upload widget -->
                    <form id="fileupload" action="" method="POST" enctype="multipart/form-data">
                        <div class="container2">
                            <div class="fileupload-buttonbar">
                                 <!-- The global progress state -->
                                <div class="fileupload-progress">
                                    <!-- The global progress bar -->
                                    <div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100">
                                        <div class="bar bar-success" style="width:0%;"></div>
                                    </div>
                                    <!-- The extended global progress state -->
                                    <div class="progress-extended"></div>
                                </div>
                                <div class="text-center">
                                    <!-- The fileinput-button span is used to style the file input field as button -->
                                    <span class="btn btn-success fileinput-button">
                                        <i class="glyphicon glyphicon-plus"></i>
                                        <span><?php echo trans('Upload_add_files');?></span>
                                        <input type="file" name="files[]" multiple="multiple">
                                    </span>
                                    <button type="submit" class="btn btn-primary start">
                                        <i class="glyphicon glyphicon-upload"></i>
                                        <span><?php echo trans('Upload_start');?></span>
                                    </button>
                                    <!-- The global file processing state -->
                                    <span class="fileupload-process"></span>
                                </div>
                            </div>
                            <!-- The table listing the files available for upload/download -->
                            <div id="filesTable">
                                <table role="presentation" class="table table-striped table-condensed small"><tbody class="files"></tbody></table>
                            </div>
                            <div class="upload-help"><?php echo trans('Upload_base_help');?></div>
                        </div>
                    </form>
                    <!-- The template to display files available for upload -->
                    <script id="template-upload" type="text/x-tmpl">
                    {% for (var i=0, file; file=o.files[i]; i++) { %}
                        <tr class="template-upload">
                            <td>
                                <span class="preview"></span>
                            </td>
                            <td>
                                <p class="name">{%=file.relativePath%}{%=file.name%}</p>
                                <strong class="error text-danger"></strong>
                            </td>
                            <td>
                                <p class="size">Processing...</p>
                                <div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="bar bar-success" style="width:0%;"></div></div>
                            </td>
                            <td>
                                {% if (!i && !o.options.autoUpload) { %}
                                    <button class="btn btn-primary start" disabled style="display:none">
                                        <i class="glyphicon glyphicon-upload"></i>
                                        <span>Start</span>
                                    </button>
                                {% } %}
                                {% if (!i) { %}
                                    <button class="btn btn-link cancel">
                                        <i class="icon-remove"></i>
                                    </button>
                                {% } %}
                            </td>
                        </tr>
                    {% } %}
                    </script>
                    <!-- The template to display files available for download -->
                    <script id="template-download" type="text/x-tmpl">
                    {% for (var i=0, file; file=o.files[i]; i++) { %}
                        <tr class="template-download">
                            <td>
                                <span class="preview">
                                    {% if (file.error) { %}
                                    <i class="icon icon-remove"></i>
                                    {% } else { %}
                                    <i class="icon icon-ok"></i>
                                    {% } %}
                                </span>
                            </td>
                            <td>
                                <p class="name">
                                    {% if (file.url) { %}
                                        <a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" {%=file.thumbnailUrl?'data-gallery':''%}>{%=file.name%}</a>
                                    {% } else { %}
                                        <span>{%=file.name%}</span>
                                    {% } %}
                                </p>
                                {% if (file.error) { %}
                                    <div><span class="label label-danger">Error</span> {%=file.error%}</div>
                                {% } %}
                            </td>
                            <td>
                                <span class="size">{%=o.formatFileSize(file.size)%}</span>
                            </td>
                            <td></td>
                        </tr>
                    {% } %}
                    </script>
                </div>
                <?php if($config['url_upload']){ ?>
                <div class="tab-pane" id="urlUpload">
                    <br/>
                    <form class="form-horizontal">
                        <div class="control-group">
                            <label class="control-label" for="url"><?php echo trans('Upload_url');?></label>
                            <div class="controls">
                                <input type="text" class="input-block-level" id="url" placeholder="<?php echo trans('Upload_url');?>">
                            </div>
                        </div>
                        <div class="control-group">
                            <div class="controls">
                                <button class="btn btn-primary" id="uploadURL"><?php echo  trans('Upload_file');?></button>
                            </div>
                        </div>
                    </form>
                </div>
                <?php } ?>
            </div>
            </div>
        </div>
    </div>
</div>
<!-- uploader div end -->

<?php } ?>
        <div class="container-fluid">

<?php
$class_ext = '';
$src = '';
if ($ftp) {
    try {
        $files = $ftp->scanDir($config['ftp_base_folder'] . $config['upload_dir'] . $rfm_subfolder . $subdir);
        if (!$ftp->isDir($config['ftp_base_folder'] . $config['ftp_thumbs_dir'] . $rfm_subfolder . $subdir)) {
            create_folder(false, $config['ftp_base_folder'] . $config['ftp_thumbs_dir'] . $rfm_subfolder . $subdir, $ftp, $config);
        }
    } catch (FtpClient\FtpException $e) {
        echo "Error: ";
        echo $e->getMessage();
        echo "<br/>Please check configurations";
        die();
    }
} else {
    $files = scandir($config['current_path'] . $rfm_subfolder . $subdir);
}

$n_files = count($files);

//php sorting
$sorted = array();
//$current_folder=array();
//$prev_folder=array();
$current_files_number = 0;
$current_folders_number = 0;

foreach ($files as $k => $file) {
    if ($ftp) {
        $date = strtotime($file['day'] . " " . $file['month'] . " " . date('Y') . " " . $file['time']);
        $size = $file['size'];
        if ($file['type'] == 'file') {
            $current_files_number++;
            $file_ext = substr(strrchr($file['name'], '.'), 1);
            $is_dir = false;
        } else {
            $current_folders_number++;
            $file_ext = trans('Type_dir');
            $is_dir = true;
        }
        $sorted[$k] = array(
            'is_dir' => $is_dir,
            'file' => $file['name'],
            'file_lcase' => strtolower($file['name']),
            'date' => $date,
            'size' => $size,
            'permissions' => $file['permissions'],
            'extension' => fix_strtolower($file_ext)
        );
    } else {


        if ($file != "." && $file != "..") {
            if (is_dir($config['current_path'] . $rfm_subfolder . $subdir . $file)) {
                $date = filemtime($config['current_path'] . $rfm_subfolder . $subdir . $file);
                $current_folders_number++;
                if ($config['show_folder_size']) {
                    list($size, $nfiles, $nfolders) = folder_info($config['current_path'] . $rfm_subfolder . $subdir . $file, false);
                } else {
                    $size = 0;
                }
                $file_ext = trans('Type_dir');
                $sorted[$k] = array(
                    'is_dir' => true,
                    'file' => $file,
                    'file_lcase' => strtolower($file),
                    'date' => $date,
                    'size' => $size,
                    'permissions' => '',
                    'extension' => fix_strtolower($file_ext)
                );

                if ($config['show_folder_size']) {
                    $sorted[$k]['nfiles'] = $nfiles;
                    $sorted[$k]['nfolders'] = $nfolders;
                }
            } else {
                $current_files_number++;
                $file_path = $config['current_path'] . $rfm_subfolder . $subdir . $file;
                $date = filemtime($file_path);
                $size = filesize($file_path);
                $file_ext = substr(strrchr($file, '.'), 1);
                $sorted[$k] = array(
                    'is_dir' => false,
                    'file' => $file,
                    'file_lcase' => strtolower($file),
                    'date' => $date,
                    'size' => $size,
                    'permissions' => '',
                    'extension' => strtolower($file_ext)
                );
            }
        }
    }
}

function filenameSort($x, $y)
{
    global $descending;

    if ($x['is_dir'] !== $y['is_dir']) {
        return $y['is_dir'];
    } else {
        return ($descending)
            ? $x['file_lcase'] < $y['file_lcase']
            : $x['file_lcase'] >= $y['file_lcase'];
    }
}

function dateSort($x, $y)
{
    global $descending;

    if ($x['is_dir'] !== $y['is_dir']) {
        return $y['is_dir'];
    } else {
        return ($descending)
            ? $x['date'] < $y['date']
            : $x['date'] >= $y['date'];
    }
}

function sizeSort($x, $y)
{
    global $descending;

    if ($x['is_dir'] !== $y['is_dir']) {
        return $y['is_dir'];
    } else {
        return ($descending)
            ? $x['size'] < $y['size']
            : $x['size'] >= $y['size'];
    }
}

function extensionSort($x, $y)
{
    global $descending;

    if ($x['is_dir'] !== $y['is_dir']) {
        return $y['is_dir'];
    } else {
        return ($descending)
            ? $x['extension'] < $y['extension']
            : $x['extension'] >= $y['extension'];
    }
}

switch ($sort_by) {
    case 'date':
        usort($sorted, 'dateSort');
        break;
    case 'size':
        usort($sorted, 'sizeSort');
        break;
    case 'extension':
        usort($sorted, 'extensionSort');
        break;
    default:
        usort($sorted, 'filenameSort');
        break;
}

if ($subdir != "") {
    $sorted = array_merge(array(array('file' => '..')), $sorted);
}

$files = $sorted;
?>
<!-- header div start -->
<div class="navbar navbar-fixed-top">
    <div class="navbar-inner">
        <div class="container-fluid">
        <button type="button" class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        </button>
        <div class="brand"><?php echo trans('Toolbar');?></div>
        <div class="nav-collapse collapse">
        <div class="filters">
            <div class="row-fluid">
            <div class="span4 half">
                <?php if($config['upload_files']){ ?>
                <button class="tip btn upload-btn" title="<?php echo  trans('Upload_file');?>"><i class="rficon-upload"></i></button>
                <?php } ?>
                <?php if($config['create_text_files']){ ?>
                <button class="tip btn create-file-btn" title="<?php echo  trans('New_File');?>"><i class="icon-plus"></i><i class="icon-file"></i></button>
                <?php } ?>
                <?php if($config['create_folders']){ ?>
                <button class="tip btn new-folder" title="<?php echo  trans('New_Folder')?>"><i class="icon-plus"></i><i class="icon-folder-open"></i></button>
                <?php } ?>
                <?php if($config['copy_cut_files'] || $config['copy_cut_dirs']){ ?>
                <button class="tip btn paste-here-btn" title="<?php echo trans('Paste_Here');?>"><i class="rficon-clipboard-apply"></i></button>
                <button class="tip btn clear-clipboard-btn" title="<?php echo trans('Clear_Clipboard');?>"><i class="rficon-clipboard-clear"></i></button>
                <?php } ?>
                <div id="multiple-selection" style="display:none;">
                <?php if($config['multiple_selection']){ ?>
                <?php if($config['delete_files']){ ?>
                <button class="tip btn multiple-delete-btn" title="<?php echo trans('Erase');?>" data-confirm="<?php echo trans('Confirm_del');?>"><i class="icon-trash"></i></button>
                <?php } ?>
                <button class="tip btn multiple-select-btn" title="<?php echo trans('Select_All');?>"><i class="icon-check"></i></button>
                <button class="tip btn multiple-deselect-btn" title="<?php echo trans('Deselect_All');?>"><i class="icon-ban-circle"></i></button>
                <?php if($apply_type!="apply_none" && $config['multiple_selection_action_button']){ ?>
                <button class="btn multiple-action-btn btn-inverse" data-function="<?php echo $apply_type;?>"><?php echo trans('Select'); ?></button>
                <?php } ?>
                <?php } ?>
                </div>
            </div>
            <div class="span2 half view-controller">
                <button class="btn tip<?php if($view==0) echo " btn-inverse";?>" id="view0" data-value="0" title="<?php echo trans('View_boxes');?>"><i class="icon-th <?php if($view==0) echo "icon-white";?>"></i></button>
                <button class="btn tip<?php if($view==1) echo " btn-inverse";?>" id="view1" data-value="1" title="<?php echo trans('View_list');?>"><i class="icon-align-justify <?php if($view==1) echo "icon-white";?>"></i></button>
                <button class="btn tip<?php if($view==2) echo " btn-inverse";?>" id="view2" data-value="2" title="<?php echo trans('View_columns_list');?>"><i class="icon-fire <?php if($view==2) echo "icon-white";?>"></i></button>
            </div>
            <div class="span6 entire types">
                <span><?php echo trans('Filters');?>:</span>
                <?php if($_GET['type']!=1 && $_GET['type']!=3 && $config['show_filter_buttons']){ ?>
                    <?php if(count($config['ext_file'])>0 or false){ ?>
                <input id="select-type-1" name="radio-sort" type="radio" data-item="ff-item-type-1" checked="checked"  class="hide"  />
                <label id="ff-item-type-1" title="<?php echo trans('Files');?>" for="select-type-1" class="tip btn ff-label-type-1"><i class="icon-file"></i></label>
                    <?php } ?>
                    <?php if(count($config['ext_img'])>0 or false){ ?>
                <input id="select-type-2" name="radio-sort" type="radio" data-item="ff-item-type-2" class="hide"  />
                <label id="ff-item-type-2" title="<?php echo trans('Images');?>" for="select-type-2" class="tip btn ff-label-type-2"><i class="icon-picture"></i></label>
                    <?php } ?>
                    <?php if(count($config['ext_misc'])>0 or false){ ?>
                <input id="select-type-3" name="radio-sort" type="radio" data-item="ff-item-type-3" class="hide"  />
                <label id="ff-item-type-3" title="<?php echo trans('Archives');?>" for="select-type-3" class="tip btn ff-label-type-3"><i class="icon-inbox"></i></label>
                    <?php } ?>
                    <?php if(count($config['ext_video'])>0 or false){ ?>
                <input id="select-type-4" name="radio-sort" type="radio" data-item="ff-item-type-4" class="hide"  />
                <label id="ff-item-type-4" title="<?php echo trans('Videos');?>" for="select-type-4" class="tip btn ff-label-type-4"><i class="icon-film"></i></label>
                    <?php } ?>
                    <?php if(count($config['ext_music'])>0 or false){ ?>
                <input id="select-type-5" name="radio-sort" type="radio" data-item="ff-item-type-5" class="hide"  />
                <label id="ff-item-type-5" title="<?php echo trans('Music');?>" for="select-type-5" class="tip btn ff-label-type-5"><i class="icon-music"></i></label>
                    <?php } ?>
                <?php } ?>
                <input accesskey="f" type="text" class="filter-input <?php echo (($_GET['type']!=1 && $_GET['type']!=3) ? '' : 'filter-input-notype');?>" id="filter-input" name="filter" placeholder="<?php echo fix_strtolower(trans('Text_filter'));?>..." value="<?php echo $filter;?>"/><?php if($n_files>$config['file_number_limit_js']){ ?><label id="filter" class="btn"><i class="icon-play"></i></label><?php } ?>

                <input id="select-type-all" name="radio-sort" type="radio" data-item="ff-item-type-all" class="hide"  />
                <label id="ff-item-type-all" title="<?php echo trans('All');?>" <?php if($_GET['type']==1 || $_GET['type']==3){ ?>style="visibility: hidden;" <?php } ?> data-item="ff-item-type-all" for="select-type-all" style="margin-rigth:0px;" class="tip btn btn-inverse ff-label-type-all"><?php echo trans('All');?></label>

            </div>
            </div>
        </div>
        </div>
    </div>
    </div>
</div>

<!-- header div end -->

    <!-- breadcrumb div start -->

    <div class="row-fluid">
    <?php
    $link = "dialog.php?" . $get_params;
    ?>
    <ul class="breadcrumb">
    <li class="pull-left"><a href="<?php echo $link?>/"><i class="icon-home"></i></a></li>
    <li><span class="divider">/</span></li>
    <?php
    $bc=explode("/",$subdir);
    $tmp_path='';
    if(!empty($bc))
    foreach($bc as $k=>$b){
        $tmp_path.=$b."/";
        if($k==count($bc)-2){
    ?> <li class="active"><?php echo $b?></li><?php
        }elseif($b!=""){ ?>
        <li><a href="<?php echo $link.$tmp_path?>"><?php echo $b?></a></li><li><span class="divider"><?php echo "/";?></span></li>
    <?php }
    }
    ?>

   <!-- <li class="pull-right"><a class="btn-small" href="javascript:void('')" id="info"><i class="icon-question-sign"></i></a></li>-->
        <li class="pull-right">
            <a href="#hascodingbilgi" class="btn-small">Bilgi</a>
            <style type="text/css">
                div#hascodingbilgi{display:none;text-align:center;z-index:999999;position:absolute;width:400px;height:150px;top:50%;left:50%;margin-left:-200px;margin-top:-75px;background-color:#fff;}
                div#hascodingbilgi:target{display:block;}
            </style>
            <div id="hascodingbilgi">
                <span style="cursor:pointer;"><a href="#" style="padding: 3px;background-color: #ddd;color: red;font-weight: 900;font-size: 20px;position: absolute;right: 0px;">X</a></span>
                <br>
                <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAADP8AAAI4CAYAAACvYK0oAAAGlmlUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4KPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iQWRvYmUgWE1QIENvcmUgNS4zLWMwMTEgNjYuMTQ1NjYxLCAyMDEyLzAyLzA2LTE0OjU2OjI3ICAgICAgICAiPgogPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4KICA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIgogICAgeG1sbnM6eG1wTU09Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9tbS8iCiAgICB4bWxuczpzdEV2dD0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL3NUeXBlL1Jlc291cmNlRXZlbnQjIgogICAgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIgogICAgeG1sbnM6eG1wRE09Imh0dHA6Ly9ucy5hZG9iZS5jb20veG1wLzEuMC9EeW5hbWljTWVkaWEvIgogICAgeG1sbnM6c3REaW09Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9EaW1lbnNpb25zIyIKICAgeG1wTU06SW5zdGFuY2VJRD0ieG1wLmlpZDo3RDkzMzg4QUMxN0VFNzExQTZEMjlGMkVGQTQzQjBERCIKICAgeG1wTU06RG9jdW1lbnRJRD0ieG1wLmRpZDo2NDgyOTk5NkJEN0VFNzExQTZEMjlGMkVGQTQzQjBERCIKICAgeG1wTU06T3JpZ2luYWxEb2N1bWVudElEPSJ4bXAuZGlkOjY0ODI5OTk2QkQ3RUU3MTFBNkQyOUYyRUZBNDNCMEREIgogICB4bXA6TWV0YWRhdGFEYXRlPSIyMDE3LTA4LTExVDIxOjE4OjU4KzAzOjAwIgogICB4bXA6TW9kaWZ5RGF0ZT0iMjAxNy0wOC0xMVQyMToxODo1NyswMzowMCIKICAgeG1wRE06dmlkZW9QaXhlbEFzcGVjdFJhdGlvPSIxMDAwMDAwLzEwMDAwMDAiCiAgIHhtcERNOnZpZGVvQWxwaGFNb2RlPSJzdHJhaWdodCIKICAgeG1wRE06dmlkZW9GcmFtZVJhdGU9IjAuMDAwMDAwIj4KICAgPHhtcE1NOkhpc3Rvcnk+CiAgICA8cmRmOlNlcT4KICAgICA8cmRmOmxpCiAgICAgIHN0RXZ0OmFjdGlvbj0ic2F2ZWQiCiAgICAgIHN0RXZ0Omluc3RhbmNlSUQ9InhtcC5paWQ6N0M5MzM4OEFDMTdFRTcxMUE2RDI5RjJFRkE0M0IwREQiCiAgICAgIHN0RXZ0OndoZW49IjIwMTctMDgtMTFUMjE6MTg6NTcrMDM6MDAiCiAgICAgIHN0RXZ0OnNvZnR3YXJlQWdlbnQ9IkFkb2JlIFByZW1pZXJlIFBybyBDUzYgKFdpbmRvd3MpIgogICAgICBzdEV2dDpjaGFuZ2VkPSIvIi8+CiAgICAgPHJkZjpsaQogICAgICBzdEV2dDphY3Rpb249InNhdmVkIgogICAgICBzdEV2dDppbnN0YW5jZUlEPSJ4bXAuaWlkOjdEOTMzODhBQzE3RUU3MTFBNkQyOUYyRUZBNDNCMEREIgogICAgICBzdEV2dDp3aGVuPSIyMDE3LTA4LTExVDIxOjE4OjU4KzAzOjAwIgogICAgICBzdEV2dDpzb2Z0d2FyZUFnZW50PSJBZG9iZSBQcmVtaWVyZSBQcm8gQ1M2IChXaW5kb3dzKSIKICAgICAgc3RFdnQ6Y2hhbmdlZD0iL21ldGFkYXRhIi8+CiAgICA8L3JkZjpTZXE+CiAgIDwveG1wTU06SGlzdG9yeT4KICAgPHhtcERNOnZpZGVvRnJhbWVTaXplCiAgICBzdERpbTp3PSIzMzI3IgogICAgc3REaW06aD0iNTY4IgogICAgc3REaW06dW5pdD0icGl4ZWwiLz4KICA8L3JkZjpEZXNjcmlwdGlvbj4KIDwvcmRmOlJERj4KPC94OnhtcG1ldGE+Cjw/eHBhY2tldCBlbmQ9InIiPz5zXQRrAAAACXBIWXMAAAsTAAALEwEAmpwYAAAKT2lDQ1BQaG90b3Nob3AgSUNDIHByb2ZpbGUAAHjanVNnVFPpFj333vRCS4iA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" alt="" />
                <br>
                <a href="https://hascoding.com" target="_blank">The HasCoding Team</a> | <a target="_blank" href="http://hasaneryilmaz.blogspot.com">Hasan ERYILMAZ</a>
                <br>
                Copyright &copy; HasCoding FileManager <?php echo date("Y");?>
            </div>
        </li>
    <?php if($config['show_language_selection']){ ?>
    <li class="pull-right"><a class="btn-small" href="javascript:void('')" id="change_lang_btn"><i class="icon-globe"></i></a></li>
    <?php } ?>
    <li class="pull-right"><a id="refresh" class="btn-small" href="dialog.php?<?php echo $get_params.$subdir."&".uniqid() ?>"><i class="icon-refresh"></i></a></li>

	<li class="pull-right">
		<div class="btn-group">
		<a class="btn dropdown-toggle sorting-btn" data-toggle="dropdown" href="#">
		<i class="icon-signal"></i>
		<span class="caret"></span>
		</a>
		<ul class="dropdown-menu pull-left sorting">
			<li class="text-center"><strong><?php echo trans('Sorting') ?></strong></li>
		<li><a class="sorter sort-name <?php if($sort_by=="name"){ echo ($descending)?"descending":"ascending"; } ?>" href="javascript:void('')" data-sort="name"><?php echo trans('Filename');?></a></li>
		<li><a class="sorter sort-date <?php if($sort_by=="date"){ echo ($descending)?"descending":"ascending"; } ?>" href="javascript:void('')" data-sort="date"><?php echo trans('Date');?></a></li>
		<li><a class="sorter sort-size <?php if($sort_by=="size"){ echo ($descending)?"descending":"ascending"; } ?>" href="javascript:void('')" data-sort="size"><?php echo trans('Size');?></a></li>
		<li><a class="sorter sort-extension <?php if($sort_by=="extension"){ echo ($descending)?"descending":"ascending"; } ?>" href="javascript:void('')" data-sort="extension"><?php echo trans('Type');?></a></li>
		</ul>
		</div>
	</li>
	<li><small class="hidden-phone">(<span id="files_number"><?php echo $current_files_number."</span> ".trans('Files')." - <span id='folders_number'>".$current_folders_number."</span> ".trans('Folders');?>)</small></li>
	<?php if($config['show_total_size']){ ?>
	<li><small class="hidden-phone"><span title="<?php echo trans('total size').$config['MaxSizeTotal'];?>"><?php echo trans('total size').": ".makeSize($sizeCurrentFolder).(($config['MaxSizeTotal'] !== false && is_int($config['MaxSizeTotal']))? '/'.$config['MaxSizeTotal'].' '.trans('MB'):'');?></span></small>
	</li>
	<?php } ?>
	</ul>
	</div>
	<!-- breadcrumb div end -->
	<div class="row-fluid ff-container">
	<div class="span12">
		<?php if( ($ftp && !$ftp->isDir($config['ftp_base_folder'].$config['upload_dir'].$rfm_subfolder.$subdir))  || (!$ftp && @opendir($config['current_path'].$rfm_subfolder.$subdir)===FALSE)){ ?>
		<br/>
		<div class="alert alert-error">There is an error! The upload folder there isn't. Check your config.php file. </div>
		<?php }else{ ?>
		<h4 id="help"><?php echo trans('Swipe_help');?></h4>
		<?php if(isset($config['folder_message'])){ ?>
		<div class="alert alert-block"><?php echo $config['folder_message'];?></div>
		<?php } ?>
		<?php if($config['show_sorting_bar']){ ?>
		<!-- sorter -->
		<div class="sorter-container <?php echo "list-view".$view;?>">
		<div class="file-name"><a class="sorter sort-name <?php if($sort_by=="name"){ echo ($descending)?"descending":"ascending"; } ?>" href="javascript:void('')" data-sort="name"><?php echo trans('Filename');?></a></div>
		<div class="file-date"><a class="sorter sort-date <?php if($sort_by=="date"){ echo ($descending)?"descending":"ascending"; } ?>" href="javascript:void('')" data-sort="date"><?php echo trans('Date');?></a></div>
		<div class="file-size"><a class="sorter sort-size <?php if($sort_by=="size"){ echo ($descending)?"descending":"ascending"; } ?>" href="javascript:void('')" data-sort="size"><?php echo trans('Size');?></a></div>
		<div class='img-dimension'><?php echo trans('Dimension');?></div>
		<div class='file-extension'><a class="sorter sort-extension <?php if($sort_by=="extension"){ echo ($descending)?"descending":"ascending"; } ?>" href="javascript:void('')" data-sort="extension"><?php echo trans('Type');?></a></div>
		<div class='file-operations'><?php echo trans('Operations');?></div>
		</div>
		<?php } ?>

        <input type="hidden" id="file_number" value="<?php echo $n_files;?>" />
        <!--ul class="thumbnails ff-items"-->
        <ul class="grid cs-style-2 <?php echo "list-view".$view;?>" id="main-item-container">
        <?php


        foreach ($files as $file_array) {
            $file=$file_array['file'];
            if($file == '.' || ( substr($file, 0, 1) == '.' && isset( $file_array[ 'extension' ] ) && $file_array[ 'extension' ] == fix_strtolower(trans( 'Type_dir' ) )) || (isset($file_array['extension']) && $file_array['extension']!=fix_strtolower(trans('Type_dir'))) || ($file == '..' && $subdir == '') || in_array($file, $config['hidden_folders']) || ($filter!='' && $n_files>$config['file_number_limit_js'] && $file!=".." && stripos($file,$filter)===false)){
                continue;
            }
            $new_name=fix_filename($file,$config);
            if($ftp && $file!='..' && $file!=$new_name){
                //rename
                rename_folder($config['current_path'].$subdir.$file,$new_name,$ftp,$config);
                $file=$new_name;
            }
            //add in thumbs folder if not exist
            if($file!='..'){
                if(!$ftp && !file_exists($thumbs_path.$file)){
                    create_folder(false,$thumbs_path.$file,$ftp,$config);
                }
            }

            $class_ext = 3;
            if($file=='..' && trim($subdir) != '' ){
            $src = explode("/",$subdir);
            unset($src[count($src)-2]);
            $src=implode("/",$src);
            if($src=='') $src="/";
                }
                elseif ($file!='..') {
                    $src = $subdir . $file."/";
                }

            ?>
                <li data-name="<?php echo $file ?>" class="<?php if($file=='..') echo 'back'; else echo 'dir';?> <?php if(!$config['multiple_selection']){ ?>no-selector<?php } ?>" <?php if(($filter!='' && stripos($file,$filter)===false)) echo ' style="display:none;"';?>><?php
                $file_prevent_rename = false;
                $file_prevent_delete = false;
                if (isset($filePermissions[$file])) {
                $file_prevent_rename = isset($filePermissions[$file]['prevent_rename']) && $filePermissions[$file]['prevent_rename'];
                $file_prevent_delete = isset($filePermissions[$file]['prevent_delete']) && $filePermissions[$file]['prevent_delete'];
                }
                ?><figure data-name="<?php echo $file ?>" data-path="<?php echo $rfm_subfolder.$subdir.$file;?>" class="<?php if($file=="..") echo "back-";?>directory" data-type="<?php if($file!=".."){ echo "dir"; } ?>">
                <?php if($file==".."){ ?>
                    <input type="hidden" class="path" value="<?php echo str_replace('.','',dirname($rfm_subfolder.$subdir));?>"/>
                    <input type="hidden" class="path_thumb" value="<?php echo dirname($thumbs_path)."/";?>"/>
                <?php } ?>
                <a class="folder-link" href="dialog.php?<?php echo $get_params.rawurlencode($src)."&".($callback?'callback='.$callback."&":'').uniqid() ?>">
                    <div class="img-precontainer">
                            <div class="img-container directory"><span></span>
                            <img class="directory-img" data-src="img/<?php echo $config['icon_theme'];?>/folder<?php if($file==".."){ echo "_back"; }?>.png" />
                            </div>
                    </div>
                    <div class="img-precontainer-mini directory">
                            <div class="img-container-mini">
                            <span></span>
                            <img class="directory-img" data-src="img/<?php echo $config['icon_theme'];?>/folder<?php if($file==".."){ echo "_back"; }?>.png" />
                            </div>
                    </div>
            <?php if($file==".."){ ?>
                    <div class="box no-effect">
                    <h4><?php echo trans('Back') ?></h4>
                    </div>
                    </a>

            <?php }else{ ?>
                    </a>
                    <div class="box">
                    <h4 class="<?php if($config['ellipsis_title_after_first_row']){ echo "ellipsis"; } ?>"><a class="folder-link" data-file="<?php echo $file ?>" href="dialog.php?<?php echo $get_params.rawurlencode($src)."&".uniqid() ?>"><?php echo $file;?></a></h4>
                    </div>
                    <input type="hidden" class="name" value="<?php echo $file_array['file_lcase'];?>"/>
                    <input type="hidden" class="date" value="<?php echo $file_array['date'];?>"/>
                    <input type="hidden" class="size" value="<?php echo $file_array['size'];?>"/>
                    <input type="hidden" class="extension" value="<?php echo fix_strtolower(trans('Type_dir'));?>"/>
                    <div class="file-date"><?php echo date(trans('Date_type'),$file_array['date']);?></div>
                    <?php if($config['show_folder_size']){ ?>
                        <div class="file-size"><?php echo makeSize($file_array['size']);?></div>
                        <input type="hidden" class="nfiles" value="<?php echo $file_array['nfiles'];?>"/>
                        <input type="hidden" class="nfolders" value="<?php echo $file_array['nfolders'];?>"/>
                    <?php } ?>
                    <div class='file-extension'><?php echo fix_strtolower(trans('Type_dir'));?></div>
                    <figcaption>
                        <a href="javascript:void('')" class="tip-left edit-button rename-file-paths <?php if($config['rename_folders'] && !$file_prevent_rename) echo "rename-folder";?>" title="<?php echo trans('Rename')?>" data-folder="1" data-permissions="<?php echo $file_array['permissions']; ?>">
                        <i class="icon-pencil <?php if(!$config['rename_folders'] || $file_prevent_rename) echo 'icon-white';?>"></i></a>
                        <a href="javascript:void('')" class="tip-left erase-button <?php if($config['delete_folders'] && !$file_prevent_delete) echo "delete-folder";?>" title="<?php echo trans('Erase')?>" data-confirm="<?php echo trans('Confirm_Folder_del');?>" >
                        <i class="icon-trash <?php if(!$config['delete_folders'] || $file_prevent_delete) echo 'icon-white';?>"></i>
                        </a>
                    </figcaption>
            <?php } ?>
                </figure>
            </li>
            <?php
            }


            $files_prevent_duplicate = array();
            foreach ($files as $nu=>$file_array) {
                $file=$file_array['file'];

                if($file == '.' || $file == '..' || $file_array['extension']==fix_strtolower(trans('Type_dir')) || !check_extension($file_array['extension'],$config) || ($filter!='' && $n_files>$config['file_number_limit_js'] && stripos($file,$filter)===false))
                    continue;
                foreach ( $config['hidden_files'] as $hidden_file ) {
                    if ( fnmatch($hidden_file, $file, FNM_PATHNAME) ) {
                        continue 2;
                    }
                }
                $filename=substr($file, 0, '-' . (strlen($file_array['extension']) + 1));
                if(strlen($file_array['extension'])===0){
                    $filename = $file;
                }
                if(!$ftp){
                    $file_path=$config['current_path'].$rfm_subfolder.$subdir.$file;
                    //check if file have illegal caracter

                    if($file!=fix_filename($file,$config)){
                        $file1=fix_filename($file,$config);
                        $file_path1=($config['current_path'].$rfm_subfolder.$subdir.$file1);
                        if(file_exists($file_path1)){
                            $i = 1;
                            $info=pathinfo($file1);
                            while(file_exists($config['current_path'].$rfm_subfolder.$subdir.$info['filename'].".[".$i."].".$info['extension'])) {
                                $i++;
                            }
                            $file1=$info['filename'].".[".$i."].".$info['extension'];
                            $file_path1=($config['current_path'].$rfm_subfolder.$subdir.$file1);
                        }

                        $filename=substr($file1, 0, '-' . (strlen($file_array['extension']) + 1));
                        if(strlen($file_array['extension'])===0){
                            $filename = $file1;
                        }
                        rename_file($file_path,fix_filename($filename,$config),$ftp,$config);
                        $file=$file1;
                        $file_array['extension']=fix_filename($file_array['extension'],$config);
                        $file_path=$file_path1;
                    }
                }else{
                    $file_path = $config['ftp_base_url'].$config['upload_dir'].$rfm_subfolder.$subdir.$file;
                }

                $is_img=false;
                $is_video=false;
                $is_audio=false;
                $show_original=false;
                $show_original_mini=false;
                $mini_src="";
                $src_thumb="";
                if(in_array($file_array['extension'], $config['ext_img'])){
                    $src = $file_path;
                    $is_img=true;

                    $img_width = $img_height = "";
                    if($ftp){
                        $mini_src = $src_thumb = $config['ftp_base_url'].$config['ftp_thumbs_dir'].$subdir. $file;
                        $creation_thumb_path = "/".$config['ftp_base_folder'].$config['ftp_thumbs_dir'].$subdir. $file;
                    }else{

                        $creation_thumb_path = $mini_src = $src_thumb = $thumbs_path. $file;

                        if (!file_exists($src_thumb)) {
                            if (!create_img($file_path, $creation_thumb_path, 122, 91, 'crop', $config)) {
                                $src_thumb = $mini_src = "";
                            }
                        }
                        //check if is smaller than thumb
                        list($img_width, $img_height, $img_type, $attr)=@getimagesize($file_path);
                        if($img_width<122 && $img_height<91){
                            $src_thumb=$file_path;
                            $show_original=true;
                        }

                        if($img_width<45 && $img_height<38){
                            $mini_src=$config['current_path'].$rfm_subfolder.$subdir.$file;
                            $show_original_mini=true;
                        }
                    }
                }
                $is_icon_thumb=false;
                $is_icon_thumb_mini=false;
                $no_thumb=false;
                if($src_thumb==""){
                    $no_thumb=true;
                    if(file_exists('img/'.$config['icon_theme'].'/'.$file_array['extension'].".jpg")){
                        $src_thumb ='img/'.$config['icon_theme'].'/'.$file_array['extension'].".jpg";
                    }else{
                        $src_thumb = "img/".$config['icon_theme']."/default.jpg";
                    }
                    $is_icon_thumb=true;
                }
                if($mini_src==""){
                $is_icon_thumb_mini=false;
                }

                $class_ext=0;
                if (in_array($file_array['extension'], $config['ext_video'])) {
                    $class_ext = 4;
                    $is_video=true;
                }elseif (in_array($file_array['extension'], $config['ext_img'])) {
                    $class_ext = 2;
                }elseif (in_array($file_array['extension'], $config['ext_music'])) {
                    $class_ext = 5;
                    $is_audio=true;
                }elseif (in_array($file_array['extension'], $config['ext_misc'])) {
                    $class_ext = 3;
                }else{
                    $class_ext = 1;
                }
                if((!($_GET['type']==1 && !$is_img) && !(($_GET['type']==3 && !$is_video) && ($_GET['type']==3 && !$is_audio))) && $class_ext>0){
?>
            <li class="ff-item-type-<?php echo $class_ext;?> file <?php if(!$config['multiple_selection']){ ?>no-selector<?php } ?>"  data-name="<?php echo $file;?>" <?php if(($filter!='' && stripos($file,$filter)===false)) echo ' style="display:none;"';?>><?php
            $file_prevent_rename = false;
            $file_prevent_delete = false;
            if (isset($filePermissions[$file])) {
            if (isset($filePermissions[$file]['prevent_duplicate']) && $filePermissions[$file]['prevent_duplicate']) {
                $files_prevent_duplicate[] = $file;
            }
            $file_prevent_rename = isset($filePermissions[$file]['prevent_rename']) && $filePermissions[$file]['prevent_rename'];
            $file_prevent_delete = isset($filePermissions[$file]['prevent_delete']) && $filePermissions[$file]['prevent_delete'];
            }
            ?>
            <figure data-name="<?php echo $file ?>" data-path="<?php echo $rfm_subfolder.$subdir.$file;?>" data-type="<?php if($is_img){ echo "img"; }else{ echo "file"; } ?>">
            <?php if($config['multiple_selection']){ ?><div class="selector">
                        <label class="cont">
                            <input type="checkbox" class="selection" name="selection[]" value="<?php echo $file;?>">
                            <span class="checkmark"></span>
                        </label>
                    </div>
                    <?php } ?>
                <a href="javascript:void('')" class="link" data-file="<?php echo $file;?>" data-function="<?php echo $apply;?>">
                <div class="img-precontainer">
                    <?php if($is_icon_thumb){ ?><div class="filetype"><?php echo $file_array['extension'] ?></div><?php } ?>
                    
                    <div class="img-container">
                        <img class="<?php echo $show_original ? "original" : "" ?><?php echo $is_icon_thumb ? " icon" : "" ?>" data-src="<?php echo $src_thumb;?>">
                    </div>
                </div>
                <div class="img-precontainer-mini <?php if($is_img) echo 'original-thumb' ?>">
                    <?php if($config['multiple_selection']){ ?>
                    <?php } ?>
                    <div class="filetype <?php echo $file_array['extension'] ?> <?php if(in_array($file_array['extension'], $config['editable_text_file_exts'])) echo 'edit-text-file-allowed' ?> <?php if(!$is_icon_thumb){ echo "hide"; }?>"><?php echo $file_array['extension'] ?></div>
                    <div class="img-container-mini">
                    <?php if($mini_src!=""){ ?>
                    <img class="<?php echo $show_original_mini ? "original" : "" ?><?php echo $is_icon_thumb_mini ? " icon" : "" ?>" data-src="<?php echo $mini_src;?>">
                    <?php } ?>
                    </div>
                </div>
                <?php if($is_icon_thumb){ ?>
                <div class="cover"></div>
                <?php } ?>
                <div class="box">
                <h4 class="<?php if($config['ellipsis_title_after_first_row']){ echo "ellipsis"; } ?>">
                <?php echo $filename;?></h4>
                </div></a>
                <input type="hidden" class="date" value="<?php echo $file_array['date'];?>"/>
                <input type="hidden" class="size" value="<?php echo $file_array['size'] ?>"/>
                <input type="hidden" class="extension" value="<?php echo $file_array['extension'];?>"/>
                <input type="hidden" class="name" value="<?php echo $file_array['file_lcase'];?>"/>
                <div class="file-date"><?php echo date(trans('Date_type'),$file_array['date'])?></div>
                <div class="file-size"><?php echo makeSize($file_array['size'])?></div>
                <div class='img-dimension'><?php if($is_img){ echo $img_width."x".$img_height; } ?></div>
                <div class='file-extension'><?php echo $file_array['extension'];?></div>
                <figcaption>
                    <form action="force_download.php" method="post" class="download-form" id="form<?php echo $nu;?>">
                    <input type="hidden" name="path" value="<?php echo $rfm_subfolder.$subdir?>"/>
                    <input type="hidden" class="name_download" name="name" value="<?php echo $file?>"/>

                    <a title="<?php echo trans('Download')?>" class="tip-right" href="javascript:void('')" <?php if($config['download_files']) echo "onclick=\"$('#form".$nu."').submit();\"" ?>><i class="icon-download <?php if(!$config['download_files']) echo 'icon-white'; ?>"></i></a>

                    <?php if($is_img && $src_thumb!=""){ ?>
                    <a class="tip-right preview" title="<?php echo trans('Preview')?>" data-featherlight="<?php echo $src;?>"  href="#"><i class=" icon-eye-open"></i></a>
                    <?php }elseif(($is_video || $is_audio) && in_array($file_array['extension'],$config['jplayer_exts'])){ ?>
                    <a class="tip-right modalAV <?php if($is_audio){ echo "audio"; }else{ echo "video"; } ?>"
                    title="<?php echo trans('Preview')?>" data-url="ajax_calls.php?action=media_preview&title=<?php echo $filename;?>&file=<?php echo $rfm_subfolder.$subdir.$file;?>"
                    href="javascript:void('');" ><i class=" icon-eye-open"></i></a>
                    <?php }elseif(in_array($file_array['extension'],$config['cad_exts'])){ ?>
                    <a class="tip-right file-preview-btn" title="<?php echo trans('Preview')?>" data-url="ajax_calls.php?action=cad_preview&title=<?php echo $filename;?>&file=<?php echo $rfm_subfolder.$subdir.$file;?>"
                    href="javascript:void('');" ><i class=" icon-eye-open"></i></a>
                    <?php }elseif($config['preview_text_files'] && in_array($file_array['extension'],$config['previewable_text_file_exts'])){ ?>
                    <a class="tip-right file-preview-btn" title="<?php echo trans('Preview')?>" data-url="ajax_calls.php?action=get_file&sub_action=preview&preview_mode=text&title=<?php echo $filename;?>&file=<?php echo $rfm_subfolder.$subdir.$file;?>"
                    href="javascript:void('');" ><i class=" icon-eye-open"></i></a>
                    <?php }elseif($config['googledoc_enabled'] && in_array($file_array['extension'],$config['googledoc_file_exts'])){ ?>
                    <a class="tip-right file-preview-btn" title="<?php echo trans('Preview')?>" data-url="ajax_calls.php?action=get_file&sub_action=preview&preview_mode=google&title=<?php echo $filename;?>&file=<?php echo $rfm_subfolder.$subdir.$file;?>"
                    href="docs.google.com;" ><i class=" icon-eye-open"></i></a>
                    <?php }else{ ?>
                    <a class="preview disabled"><i class="icon-eye-open icon-white"></i></a>
                    <?php } ?>
                    <a href="javascript:void('')" class="tip-left edit-button rename-file-paths <?php if($config['rename_files'] && !$file_prevent_rename) echo "rename-file";?>" title="<?php echo trans('Rename')?>" data-folder="0" data-permissions="<?php echo $file_array['permissions']; ?>">
                    <i class="icon-pencil <?php if(!$config['rename_files'] || $file_prevent_rename) echo 'icon-white';?>"></i></a>

                    <a href="javascript:void('')" class="tip-left erase-button <?php if($config['delete_files'] && !$file_prevent_delete) echo "delete-file";?>" title="<?php echo trans('Erase')?>" data-confirm="<?php echo trans('Confirm_del');?>">
                    <i class="icon-trash <?php if(!$config['delete_files'] || $file_prevent_delete) echo 'icon-white';?>"></i>
                    </a>
                    </form>
                </figcaption>
            </figure>
        </li>
            <?php
            }
            }

    ?></div>
        </ul>
        <?php } ?>
    </div>
    </div>
</div>

<script>
    var files_prevent_duplicate = [];
    <?php foreach ($files_prevent_duplicate as $key => $value): ?>
    files_prevent_duplicate[<?php echo $key;?>] = '<?php echo $value;?>';
    <?php endforeach;?>
</script>

    <!-- loading div start -->
    <div id="loading_container" style="display:none;">
        <div id="loading" style="background-color:#000; position:fixed; width:100%; height:100%; top:0px; left:0px;z-index:100000"></div>
        <img id="loading_animation" src="img/storing_animation.gif" alt="loading" style="z-index:10001; margin-left:-32px; margin-top:-32px; position:fixed; left:50%; top:50%">
    </div>
    <!-- loading div end -->

    <!-- player div start -->
    <div class="modal hide" id="previewAV">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h3><?php echo trans('Preview'); ?></h3>
        </div>
        <div class="modal-body">
            <div class="row-fluid body-preview">
            </div>
        </div>
    </div>

    <!-- player div end -->
    <?php if ( $config['tui_active'] ) { ?>

        <div id="tui-image-editor" style="height: 800px;" class="hide">
            <canvas></canvas>
        </div>

        <script>
            var tuiTheme = {
                <?php foreach ($config['tui_defaults_config'] as $aopt_key => $aopt_val) {
                    if ( !empty($aopt_val) ) {
                        echo "'$aopt_key':".json_encode($aopt_val).",";
                    }
                } ?>
            }; 
        </script>

        <script>
        if (image_editor) { 
            //TUI initial init with a blank image (Needs to be initiated before a dynamic image can be loaded into it)
            var imageEditor = new tui.ImageEditor('#tui-image-editor', {
                includeUI: {
                     loadImage: {
                        path: 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7',
                        name: 'Blank'
                     },
                     theme: tuiTheme,
                     initMenu: 'filter',
                     menuBarPosition: '<?php echo $config['tui_position'] ?>'
                 },
                cssMaxWidth: 1000, // Component default value: 1000
                cssMaxHeight: 800,  // Component default value: 800
                selectionStyle: {
                    cornerSize: 20,
                    rotxatingPointOffset: 70
                }
            });
            //cache loaded image
            imageEditor.loadImageFromURL = (function() {
                var cached_function = imageEditor.loadImageFromURL;
                function waitUntilImageEditorIsUnlocked(imageEditor) {
                    return new Promise((resolve,reject)=>{
                        const interval = setInterval(()=>{
                            if (!imageEditor._invoker._isLocked) {
                                clearInterval(interval);
                                resolve();
                            }
                        }, 100);
                    })
                }
                return function() {
                    return waitUntilImageEditorIsUnlocked(imageEditor).then(()=>cached_function.apply(this, arguments));
                };
            })();

            //Replace Load button with exit button
            $('.tui-image-editor-header-buttons div').
            replaceWith('<button class="tui-image-editor-exit-btn" ><?php echo trans('Image_Editor_Exit');?></button>');
            $('.tui-image-editor-exit-btn').on('click', function() {
                exitTUI();
            });
            //Replace download button with save
            $('.tui-image-editor-download-btn').
            replaceWith('<button class="tui-image-editor-save-btn" ><?php echo trans('Image_Editor_Save');?></button>');
            $('.tui-image-editor-save-btn').on('click', function() {
                saveTUI();
            });

            function exitTUI()
            {
                imageEditor.clearObjects();
                imageEditor.discardSelection();
                $('#tui-image-editor').addClass('hide');
            }

            function saveTUI()
            {
                show_animation();
                newURL = imageEditor.toDataURL();
                $.ajax({
                    type: "POST",
                    url: "ajax_calls.php?action=save_img",
                    data: { url: newURL, path:$('#sub_folder').val()+$('#fldr_value').val(), name:$('#tui-image-editor').attr('data-name') }
                }).done(function( msg ) {
                    exitTUI();
                    d = new Date();
                    $("figure[data-name='"+$('#tui-image-editor').attr('data-name')+"']").find('.img-container img').each(function(){
                    $(this).attr('src',$(this).attr('src')+"?"+d.getTime());
                    });
                    $("figure[data-name='"+$('#tui-image-editor').attr('data-name')+"']").find('figcaption a.preview').each(function(){
                    $(this).attr('data-url',$(this).data('url')+"?"+d.getTime());
                    });
                    hide_animation();
                });
                return false;
            }
        }
        </script>
    <?php } ?>
    <script>
        var ua = navigator.userAgent.toLowerCase();
        var isAndroid = ua.indexOf("android") > -1; //&& ua.indexOf("mobile");
        if (isAndroid) {
            $('li').draggable({disabled: true});
        }
    </script>
</body>
</html>
