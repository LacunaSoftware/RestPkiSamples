<nav class="navbar navbar-inverse navbar-fixed-top">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar"
                    aria-expanded="false" aria-controls="navbar">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="index.php">REST PKI Samples</a>
        </div>
        <div id="navbar" class="collapse navbar-collapse">
            <ul class="nav navbar-nav">
                <li><a href="authentication.php">Authentication</a></li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
                       aria-expanded="false">PAdES signature <span class="caret"></span></a>
                    <ul class="dropdown-menu">
                        <li><a href="pades-signature.php">With file already on server</a></li>
                        <li><a href="upload.php?goto=pades-signature">With file uploaded by user</a></li>
                        <li><a href="upload.php?goto=open-pades-signature">Open/validate existing signature</a></li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
                       aria-expanded="false">CAdES signature <span class="caret"></span></a>
                    <ul class="dropdown-menu">
                        <li><a href="cades-signature.php">With file already on server</a></li>
                        <li><a href="upload.php?goto=cades-signature">With file uploaded by user</a></li>
                        <li><a href="upload.php?goto=open-cades-signature">Open/validate existing signature</a></li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
                       aria-expanded="false">XML signature <span class="caret"></span></a>
                    <ul class="dropdown-menu">
                        <li><a href="xml-full-signature.php">Full XML signature (enveloped signature)</a></li>
                        <li><a href="xml-element-signature.php">XML element signature</a></li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
                       aria-expanded="false">Batch signature <span class="caret"></span></a>
                    <ul class="dropdown-menu">
                        <li><a href="batch-signature.php">Simple batch signature</a></li>
                    </ul>
                </li>
            </ul>
        </div>
        <!--/.nav-collapse -->
    </div>
</nav>
