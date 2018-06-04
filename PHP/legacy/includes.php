<?php
require __DIR__ . '/vendor/autoload.php';
$config = getConfig();

// jQuery and other libs (used only to provide a better user experience, but NOT required to use the Web PKI component).
?>

<link href="content/css/bootstrap.css" rel="stylesheet"/>
<link href="content/css/bootstrap-theme.css" rel="stylesheet"/>
<link href="content/css/site.css" rel="stylesheet"/>
<script src="content/js/jquery-1.11.3.js"></script>
<script src="content/js/jquery.blockUI.js"></script>
<script src="content/js/bootstrap.js"></script>

<script>
    var _webPkiLicense = '<?php echo $config['webPki']['license']; ?>';
    var _restPkiEndpoint = '<?php echo $config['restPki']['endpoint']; ?>';
</script>
