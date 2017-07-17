<div class="ulogin_form">
    <?php if (empty($uloginid)) { ?>
        <div id="uLogin" data-ulogin="display=panel;theme=flat;fields=first_name,last_name,email;optional=phone,city,country,nickname,sex,photo_big,bdate,photo;providers=vkontakte,odnoklassniki,mailru,facebook;hidden=other;redirect_uri=<?php echo $redirect_uri; ?>;callback=<?php echo $callback; ?>"></div>
    <?php } else { ?>
        <div id="uLogin" data-uloginid="<?php echo $uloginid; ?>" data-ulogin="redirect_uri=<?php echo $redirect_uri; ?>;callback=<?php echo $callback; ?>"></div>
    <?php } ?>
</div>
<div style="clear:both"></div>
