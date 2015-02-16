<div class="ulogin_profile">
    <legend><?php echo $ulogin_profile_title; ?></legend>

    <div class="panel panel-default ulogin_panel">
        <div class="panel-heading"><?php echo $add_account; ?></div>
        <?php echo $ulogin_form; ?>
        <div class="ulogin_note"><small><?php echo $add_account_explain; ?></small></div>

        <div class="panel-heading"><?php echo $delete_account; ?></div>
        <div class="ulogin_accounts can_delete">
            <?php if (!empty($networks) && is_array($networks)) { ?>
                <?php foreach ( $networks as $network ) { ?>
                    <div data-ulogin-network='<?php echo $network;?>'
                         class="ulogin_provider big_provider <?php echo $network;?>_big"
                         onclick="uloginDeleteAccount('<?php echo $network;?>')"></div>
                <?php } ?>
            <?php } ?>
        </div><div style="clear:both"></div>
        <div class="ulogin_note"><small><?php echo $delete_account_explain; ?></small></div>
    </div>
</div>

