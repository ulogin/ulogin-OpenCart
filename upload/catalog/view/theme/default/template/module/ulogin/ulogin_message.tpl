<div id="ulogin-message-box">
    <div class="ulogin-message-success alert alert-success" <?php if (empty($ulogin_success)) { ?>style="display: none"<?php } ?>>
        <i class="fa fa-check-circle"></i>
        <span class="ulogin-message"><?php if (!empty($ulogin_success)) {echo $ulogin_success;} ?></span>
    </div>

    <div class="ulogin-message-warning alert alert-warning" <?php if (empty($ulogin_error_warning)) { ?>style="display: none"<?php } ?>>
        <i class="fa fa-exclamation-circle"></i>
        <span class="ulogin-message"><?php if (!empty($ulogin_error_warning)) {echo $ulogin_error_warning;} ?></span>
    </div>
</div>