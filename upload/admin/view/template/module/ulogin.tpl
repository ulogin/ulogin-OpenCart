<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
    <div class="page-header">
        <div class="container-fluid">
            <div class="pull-right">
                <button type="submit" form="form-ulogin" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
                <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a></div>
            <h1><?php echo $heading_title; ?></h1>
            <ul class="breadcrumb">
                <?php foreach ($breadcrumbs as $breadcrumb) { ?>
                <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
                <?php } ?>
            </ul>
        </div>
    </div>
    <div class="container-fluid">
        <div class="panel panel-default">
            <div class="panel-body">
                <?php echo $text_module_description; ?>
            </div>
        </div>
        <?php if ($error_warning) { ?>
        <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php } ?>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>
            </div>
            <div class="panel-body">
                <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-ulogin" class="form-horizontal">
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-name"><?php echo $entry_name; ?></label>
                        <div class="col-sm-10">
                            <input type="text" name="name" value="<?php echo $name; ?>" placeholder="<?php echo $entry_name; ?>" id="input-name" class="form-control" />
                            <?php if ($error_name) { ?>
                            <div class="text-danger"><?php echo $error_name; ?></div>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-uloginid"><?php echo $entry_uloginid; ?></label>
                        <div class="col-sm-10">
                            <input
                                    type="text"
                                    name="uloginid"
                                    value="<?php echo isset($uloginid) ? $uloginid : ''; ?>"
                                    placeholder="<?php echo $entry_uloginid_pl; ?>"
                                    id="input-uloginid"
                                    class="form-control"
                                    maxlength="8"
                                    />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-type"><?php echo $entry_type; ?></label>
                        <div class="col-sm-10">
                            <select name="type" id="input-type" class="form-control">
                                <?php if ($type == 'online') { ?>
                                    <option value="offline"><?php echo $text_type_offline; ?></option>
                                    <option value="online" selected="selected"><?php echo $text_type_online; ?></option>
                                    <option value="online_edit"><?php echo $text_type_online_edit_page; ?></option>
                                <?php } elseif ($type == 'offline') { ?>
                                    <option value="offline" selected="selected"><?php echo $text_type_offline; ?></option>
                                    <option value="online"><?php echo $text_type_online; ?></option>
                                    <option value="online_edit"><?php echo $text_type_online_edit_page; ?></option>
                                <?php } elseif ($type == 'online_edit') { ?>
                                    <option value="offline"><?php echo $text_type_offline; ?></option>
                                    <option value="online"><?php echo $text_type_online; ?></option>
                                    <option value="online_edit" selected="selected"><?php echo $text_type_online_edit_page; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-status"><?php echo $entry_status; ?></label>
                        <div class="col-sm-10">
                            <select name="status" id="input-status" class="form-control">
                                <?php if ($status) { ?>
                                <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                                <option value="0"><?php echo $text_disabled; ?></option>
                                <?php } else { ?>
                                <option value="1"><?php echo $text_enabled; ?></option>
                                <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php echo $footer; ?>