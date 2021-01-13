<link rel="stylesheet" type="text/css" href="<?= Yii::app()->baseUrl ?>/static/css/jquery.dataTables.css">
<script type="text/javascript" charset="utf8" src="<?= Yii::app()->baseUrl ?>/static/js/jquery.dataTables.js"></script>


<style>
    .tabs_links li a {
        padding: 0px 20px;
    }

    .input_form_table {
        /*border-radius: 5px;*/
        height: 30px;
        /*width: 98%;*/
        padding: 5px 8px;
        /*float: left;*/
        font-size: 12px;
    }

    .input_form_table:focus {
        border: 1px solid cornflowerblue;
    }

    /*Див кнопки именинников*/
    .birthDates {
        float: right;
        padding-right: 27px;
    }

    /*Кол-во*/
    .birthDaysQuantity {
        border: 2px solid white;
        font-size: 17px;
        padding: 2px 5px 2px 5px;
    }

    .birthDaysQuantity:hover {
        background-color: #ffbc0c;
    }

    /*Див картинок*/
    .birthDaysImages {
    }

    /*Шляпа на кнопке*/
    .birthDayHat {
        width: 30px;
        position: absolute;
        z-index: 2;
        margin: -16px;
        padding-left: 0px;
        -moz-transform: rotate(15deg);
        -ms-transform: rotate(15deg);
        -webkit-transform: rotate(15deg);
        -o-transform: rotate(15deg);
        transform: rotate(-45deg);
    }

    /*Шарик за кнопкой*/
    .birthDayBallon {
        position: absolute;
        width: 30px;
        margin-top: -28px;
        padding-left: 0px;
    }

</style>

<?
$regions = UserRegion::model()->findAll();
$accessToAllRegions = Tools::checkAccess('customer', 'allRegions', 'edit');
$accessToNoRegions = Tools::checkAccess('customer', 'noRegions', 'edit');

$region_id = Yii::app()->request->getParam('region');

$userRegions = [];
$userRegionAccess = UserRegionAccess::model()->findAll("userId = " . Yii::app()->user->id);
foreach ($userRegionAccess as $ura) {
    $userRegions[] = $ura->regionId;
}

if (!in_array(Yii::app()->user->regionId, $userRegions)) {
    $userRegions[] = Yii::app()->user->regionId;
}

// заполняем список id регионов
$regionIds = array();
if ($accessToAllRegions) {
    $regionIds[] = 'all';
}
foreach ($regions as $r) {
    if ($r->id != 0) {
        if (!Yii::app()->user->regionId || $accessToAllRegions || in_array($r->id, $userRegions)/* Yii::app()->user->regionId == $r->id*/) {
            $regionIds[] = $r->id;
        }
    } elseif ($accessToNoRegions) {
        $regionIds[] = $r->id;
    }
}

// проверяем полученный регион на
if ($region_id == null || !in_array($region_id, $regionIds)) {
    // ищем первый регион, в котарый пользователь имеет доступ
    $region_id = -1;
    foreach ($regionIds as $r) {
        if ($r != '0' && $region_id == -1) {
            $region_id = $r;
        }
    }
    if ($region_id == -1 && in_array(0, $regionIds)) {
        $region_id = 0;
    }
}


// ищем заказчиков согласно выбранному региону

$criteria = new CDbCriteria();
if ($region_id != 'all') {
    $criteria->addCondition('t.regionId = ' . $region_id);
}
$criteria->order = 't.name DESC';

//$customers = Customer::model()->with('contacts', 'holding')->findAll($criteria);
$customers = Customer::model()->findAll($criteria);
$birthDayContacts = CustomerContacts::getBirthDays();
?>

<!-- Кнопка именинников -->
<? if (Tools::checkAccess('customer', 'birthDates', 'view')): ?>
    <div class='birthDates'>
        <div class="birthDaysImages">
            <img class="birthDayHat" src="/static/img/bd_hat.png" alt="YEAH!">
            <img class="birthDayBallon" src="/static/img/bd_ballon.png" alt="YEAH!">
        </div>
        <div class="birthDaysButton">
            <button href='/customer/birthDates' id='fancy' style='text-decoration: none; width: 130px'
                    class="green_btn">
            <span>
                Именинники
                <? if ($birthDayContacts['quantity']): ?>
                    <i class="icountNotice ttl tooltipstered birthDaysQuantity"><?= $birthDayContacts['quantity'] ?></i>
                <? endif; ?>
            </span>
            </button>
        </div>
    </div>
<? endif; ?>

<?php if (true) { ?>
    <table style="width: 100%">
        <tr>
            <td>
                <ul class="tabs_links clearfix">
                    <?
                    foreach ($regions as $region)
                        if (in_array($region->id, $regionIds) && $region->id != 0) { ?>
                            <li <?= $region->id == $region_id ? ' class="active" ' : '' ?>>
                                <a href="<?= Yii::app()->baseUrl ?>/customer/index/region/<?= $region->id ?>/"><?= $region->name ?></a>
                            </li>
                            <?
                        }
                    if ($accessToAllRegions) { ?>
                        <li <?= $region_id == 'all' ? ' class="active" ' : ' style="background-color: #323232; color: #FFFFFF "'; ?>>
                            <a onmouseover="this.style.color='#FFE83B';" onmouseout="this.style.color='#FFFFFF';"
                               style="color: #FFFFFF"
                               href="<?= Yii::app()->baseUrl ?>/customer/index/region/all/">Все регионы</a>
                        </li>
                        <?php
                    }
                    ?>
                    <? if ((Tools::checkAccess('CUSTOMER', 'customer', 'edit'))
                        && in_array(Yii::app()->user->role, array('director', 'kdrBoss', 'uir', 'admin'))) : ?>
                        <li class="active">
                            <a onmouseover="this.style.color='#FFFFFF';" onmouseout="this.style.color='#131212';"
                               style="color: #131212" href="/customer/index/region/all/">Назад к базе заказчиков</a>
                        </li>
                    <? endif; ?>
                </ul>
            </td>
            <? if (in_array('0', $regionIds)) { ?>
                <td style="text-align: right; width: 160px">
                    <ul class="tabs_links clearfix">
                        <li <?= $region_id == '0' ? ' class="active" ' : ' style="background-color: #323232; color: #FFFFFF "'; ?>>
                            <a onmouseover="this.style.color='#FFE83B';" onmouseout="this.style.color='#FFFFFF';"
                               style="color: #FFFFFF"
                               href="<?= Yii::app()->baseUrl ?>/customer/index/region/0/">Без региона</a>
                        </li>
                    </ul>
                </td>
            <? } ?>
        </tr>
    </table>
<? } ?>


<table style="width: 100%" id="customers_table" class="display dataTable no-footer">
    <thead>
    <tr style=" background: #ffc912;">
        <td><b>ID</b></td>
        <td><b>ЕГРПОУ</b></td>
        <td><b>Наименование предприятия</b></td>
        <td><b>Регион</b></td>
        <!--            <td>Создал</td>-->
        <td><b>Месторасположения</b></td>

        <td><b>Контактное лицо</b></td>
        <td><b>Холдинг</b></td>
        <td><b>Работа</b></td>
        <!--            <td><b>Архив</b></td>-->
    </tr>
    </thead>
    <tbody>
    <? $num = 1; ?>
    <? $editRegion = Tools::checkAccess('customer', 'editRegionId', 'edit'); ?>
    <?
    $regionList = array();
    if ($editRegion) {
        $regionList = UserRegion::model()->findAll();
    }

    ?>
    <?php foreach ($customers as $customer) { ?>
        <? //$contacts = CustomerContacts::model()->findAll('customerId = ' . $customer->id . ' AND isDell = 0');
        if ($customer->in_archive == 1) {
            $contacts = $customer->contacts; ?>
        <tr class="tr_table" id="in_archive<?= $customer->id ?>" style="border-bottom: solid #b6b6b6 1px;">
            <td class="tdCustomer" id="id"><?= $customer->id ?></td>
            <? if ($customer && $customer->isChild()): ?>
                <td class="tdCustomer"
                    id="egrpou"><?= ($customer && $customer->parent->egrpou) ? $customer->parent->egrpou : $customer->egrpou ?></td>
            <? else : ?>
                <td class="tdCustomer"
                    id="egrpou"><?= ($customer && $customer->egrpou) ? $customer->egrpou : "" ?></td>
            <? endif; ?>

            <td class="tdCustomer" id="name"><b><?= $customer->name ?></b></td>
            <td class="tdCustomer" id="adres">
                <? if ($editRegion) { ?>
                    <select class="noUniform input_form_table" id="reg<?= $customer->id ?>"
                            onchange="setRegion(<?= $customer->id ?>)">
                        <? foreach ($regionList as $rl) { ?>
                            <option value='<?= $rl->id ?>' <?= $rl->id == $customer->regionId ? 'selected' : '' ?>
                                    data-oldvalue="<?= $rl->id ?>"><?= $rl->name ?></option>
                        <? } ?>
                    </select>
                <? } else { ?>
                    <?= $customer->innerRegion->name ?>
                <? } ?>
            </td>
            <td class="tdCustomer" id="region"><?= $customer->region ?></td>
            <td class="tdCustomer" id="fio" style="line-height: 2; min-width: 350px;">
                <? if ($contacts): ?>
                    <ul>
                        <? foreach ($contacts as $contact): ?>
                            <? if ($contact->isDell == 0): ?>
                                <li>
                                    <? if ($contact->name): ?>
                                        <? $phone = json_decode($contact->phone); ?>
                                        <span><?= $contact->name; ?>, </span>
                                    <? endif; ?>
                                    <? if ($contact->position): ?>
                                        <span><?= $contact->position; ?></span>
                                    <? endif; ?>
                                </li>
                            <? endif; ?>
                        <? endforeach; ?>
                    </ul>
                <? endif; ?>
            </td>
            <? $cc = "";
            if ($customer->distance) $cc .= $customer->distance . " км"; ?>
            <td style="text-align: center; width: 200px">
                <? if ($customer->holding) {
                    echo $customer->holding->name;
                } else {
                    echo 'Не указан';
                } ?>
            </td>
            <? if (in_array(Yii::app()->user->role, array('director', 'kdrBoss', 'uir', 'admin'))) : ?>
                <? if (Tools::checkAccess('CUSTOMER', 'customer', 'edit') || Tools::checkAccess('CUSTOMER', 'customer', 'view')) : ?>
                    <td style="width: 135px">
                        <? if (Tools::checkAccess('CUSTOMER', 'customer', 'edit')) : ?>
                            <a href="/customer/edit/id/<?= $customer->id ?>" style="text-decoration: none;"
                               target="_blank">
                                <img width="30px" height="30px" class="ttl kr_img" src="/static/images/edit4.png"
                                     title="Редактировать">
                            </a>
                        <? else: ?>
                            <img width="30px" height="30px" class="ttl kr_img" src="/static/images/edit4.png"
                                 title="Редактировать(Недоступно)" style="opacity: 0.25">
                        <? endif; ?>
                        <? if (Tools::checkAccess('CUSTOMER', 'customer', 'view')) : ?>
                            <a href="/customer/show/id/<?= $customer->id ?>" style="text-decoration: none;"
                               target="_blank">
                                <img width="30px" height="30px" class="ttl kr_img" src="/static/images/viev1.png"
                                     title="Просмотр" style="padding-left: 10px; padding-right: 10px">
                            </a>
                        <? else: ?>
                            <img width="30px" height="30px" class="ttl kr_img" src="/static/images/viev1.png"
                                 title="Просмотр(Недоступно)"
                                 style="padding-left: 10px; padding-right: 10px; opacity: 0.25">
                        <? endif; ?>
                        <? if (Tools::checkAccess('CUSTOMER', 'customer', 'edit')) : ?>
                            <a href="/Customer/ShowModalAddToArchiveCustomer/id/<?= $customer->id ?>"
                               id="arch<?= $customer->id ?>" class="modal-inline"
                               data-custom='<?= $customer->id ?>'>
                                <img width="30px" height="30px" id="arch-<?= $customer->id ?>"
                                     class="img-comp ttl contact_buttons arch1<?= $customer->id ?>"
                                     data-id="<?= $customer->id ?>"
                                     src="/static/img/history_noempty.png" alt='' title="Востановить из архива"
                                     style='cursor: pointer'>
                            </a>
                        <? else: ?>
                            <img width="30px" height="30px" id="arch-<?= $customer->id ?>"
                                 class="img-comp ttl contact_buttons arch1<?= $customer->id ?>"
                                 data-id="<?= $customer->id ?>"
                                 src="/static/img/history_noempty.png" alt='' title="Архив"
                                 style='cursor: pointer; opacity: 0.25'>
                        <? endif; ?>
                    </td>
                <? endif; ?>
                </tr>
            <? endif; ?>
            <? $num++ ?>
        <?php } ?>
    <?php } ?>
    </tbody>
</table>

<a id="fancy" href="#"></a>

<script>

    $(document).ready(function () {
        $(".modal-inline").fancybox({
            'transitionIn': 'none',
            'transitionOut': 'none',
            'titlePosition': 'inside',
        });
    });

</script>

<script>
    //    $('#fancy').on('mousedown',function(e){
    //        console.log(e.which);
    //        if(e.which === 2){
    //            alert('Действие невозможно');
    //            e.preventDefault();
    //        }
    //    });


    <? if ($editRegion) { ?>
    function setRegion(id) {
        var el = $('#reg' + id);
        var regionId = $(el).val();
        var oldvalue = $(el).data('oldvalue');
        $.blockUI();
        $.post('/customer/setRegion/id/' + id + '/val/' + regionId, function (data) {
            $.unblockUI();
            data = $.parseJSON(data);
            if (data.success) {
                sendNotify('Успешно!!!', 'success');
                $(el).data('oldvalue', $(el).val());
            } else {
                sendNotify(data.error, 'error');
                $(el).val($(el).data('oldvalue'));
            }
        })
    }
    <? } ?>

    var cookie_name = 'customers_table_search';
    $(function () {

        var table = $('#customers_table').DataTable({
            "aoColumnDefs": [
                {"bSortable": false, "aTargets": [7]},
            ],
            "iDisplayLength": 100, // количество строк на странице
            search: {search: $.cookie(cookie_name)},// установка значения фильтра
            "order": [[2, "asc"]], // сортировка по умолчанию (колонка 0 по убыванию)
            "oLanguage": {
                "sInfo": "Всего доступно _TOTAL_ записей, показано с _START_ по _END_",
                "sInfoEmpty": "Доступно 0 записей",
                "sEmptyTable": "Нет данных для отображения",
                "sInfoFiltered": " - отфильтровано из _MAX_ записей",
                "sSearch": "Поиск:",
                "sLengthMenu": "Показывать _MENU_ записей на странице",
                "oPaginate": {
                    "sPrevious": "Назад",
                    "sNext": "Вперед"
                }
            },
            "initComplete": function (settings, json) {
                var ff = $('.dataTables_filter').find('label');
                $(ff).append(
                    "<a href='/customer/holdings/' style='text-decoration: none;'>" +
                    "<img style='width: 32px;vertical-align: bottom;padding-left: 10px;' src='/static/img/insurance_blue.png' title='Перейти к холдингам'>" +
                    "</a>" +
                    "<img src=\"/static/img/excel.png\" data-format=\"xls\" data-region=\"<?= $region_id; ?>\" alt=\"Распечатать EXCEL\" title=\"Распечатать EXCEL\" class=\"exportXls\" style=\"width: 30px; height: 30px; cursor:pointer; padding-left: 5px;\">"
                );

                $('.exportXls').on('click', function () {

                    let sendFormat = $(this).data('format');
                    let region = $(this).data('region');
                    window.open('/customer/exportCustomers/format/' + sendFormat + '/region/' + region);
                });
            }
        }).on('search.dt', function () {
            $.cookie(cookie_name, table.search(), {path: '/customer'})
        });

        $('#fancy').fancybox({
            'autoScale': true,
            'titlePosition': 'inside',
            'transitionIn': 'none',
            'transitionOut': 'none',
//            'modal'         : true,
            'onCleanup': function () {
            }
        });
    });
</script>

<!------------- DATA TABLE --------------->
<style>

    /*Вся таблица со всеми фильтрами поисками и т.д.*/
    .dataTables_wrapper {
        bottom: 18px;
    }

    /*Фильтра показа кол-ва страниц*/
    .dataTables_length {
        padding-top: 16px;
    }

</style>
<!------------- END ---------------->

