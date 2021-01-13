<!--------------------------------- PHP функци (рендеры)  -------------------------------------->


<!--<div>-->
<!--    <input style="padding: 130px 0 0 300px" type="checkbox" name="Главная"  /><br>-->
<!--    <span style="ont-size 13px; font-family: Arial, Tahoma sans-serif; cursor: pointer"><b>Главное</b><br></span>-->
<!--    <input style="padding: 140px 0 0 300px" type="checkbox" name="Дочерняя"  /><br>-->
<!--    <span style="ont-size 13px; font-family: Arial, Tahoma sans-serif; cursor: pointer"><b>Дочерняя</b><br></span>-->
<!--</div>-->

<!--<style>-->
<!--    .rud_bt_name {-->
<!--        font-size 13px;-->
<!--        font-family: Arial, Tahoma sans-serif;-->
<!--        cursor: pointer;-->
<!--    }-->
<!---->
<!--    .rudio_bt {-->
<!--        padding: 130px 0 0 300px-->
<!--    }-->
<!--</style>-->
<!---->
<!--<div>-->
<!--    <input class='rud_bt' type="checkbox" name="main"><br>-->
<!--    <span class='rud_bt_name'><b>Главная</b><br></span>-->
<!--    <input class='rud_bt' type="checkbox" name="child"><br>-->
<!--    <span class='rud_bt_name'><b>Дочерняя</b><br></span>-->
<!--</div>";-->

<?php

//--------------------------------Обычный ряд с вводом данных---------------------------------
function renderTr($name, $id, $value, $readOnly, $length)
{

    $renderString = "<tr class='line_in_form_table'>" .
        "<td class='cell_in_form_table left_cell_in_form_table'>" .
        "<div><b>" . $name . "</b></div>" .
        "</td>" .
        "<td class='cell_in_form_table'>" .
        "<input " . ($readOnly ? "readonly disabled" : "") . " maxlength='" . $length . "' type='text' id='" . $id . "' class='input_form_table' value='" . $value . "'>" .
        "</td>" .
        "</tr>";
    echo $renderString;
}

//------------------------------END---------------------------------

//-----------------------------------Ряд холдинга-------------------------------------
function renderTrHolding($name, $id, $customerHoldings, $holdings, $readOnly)
{
    $renderString = "<tr class='line_in_form_table'>" .
        "<td class='cell_in_form_table left_cell_in_form_table'>" .
        "<div><b>" . $name . "</b></div>" .
        "</td>" .
        "<td class='cell_in_form_table'>";
    $renderString .= "<select " . ($readOnly ? "readonly disabled" : "") . " id='" . $id . "' class='noUniform input_form_table' style='width: 60%;height: 30px;margin-top:10px; margin-bottom:10px;'>";
    if ($customerHoldings) {
        $renderString .= "<option selected value='" . $customerHoldings->id . "'>Текущий холдинг: " . $customerHoldings->name . "</option>" .
            "<option value='0'>Без холдинга</option>";
    } else {
        $renderString .= "<option selected value='0'>Не указан</option>";
    }
    foreach ($holdings as $holding) {
        $renderString .= "<option value='$holding->id'>" . $holding->name . "</option>";
    }
    $renderString .= "</select> . 
                            </td>" .
        "</tr>";
    echo $renderString;
}

//-----------------------------------END----------------------------------------------


//-----------------------------------Ряд регионов-------------------------------------
function renderTrRegion($name, $id, $customerRegionId, $regions, $readOnly)
{
    $users = Yii::app()->user;
    $xx = Yii::app()->user->regionId;
   // $users11 = Yii::app()->user->regionId;
    $userRegions = UserRegionAccess::getUserRegionList($users);


    // $action == 'create' ? $idReg = $id : $idReg = 'Не указан';
    // $renderString .= "<option selected value='0'>Не указан</option>";
    $id2 = 'regionid2';

    $renderString = "<tr class='line_in_form_table'>" .
        "<td class='cell_in_form_table left_cell_in_form_table'>" .
        "<div><b>" . $name . "</b></div>" .
        "</td>" .
        "<td class='cell_in_form_table'>";
    $renderString .= "<select " . ($readOnly ? "readonly disabled" : "") . " id='" . $id . "' value='' class='noUniform input_form_table' style='width: 60%; height: 30px; margin-top:10px; margin-bottom:10px;'>";
    $renderString .= "<option id='regionId2' selected value='0'>Регион не выбран</option>";
    foreach (array_slice($regions, 1) as $r) {
        if (Yii::app()->user->regionId != 0){
            if (in_array($r->id, $userRegions)) {
                $renderString .= "<option value='$r->id' " . ($r->id == $customerRegionId ? 'selected' : '') . " >" . $r->name . "</option>";
            }
        }
        else{
                $renderString .= "<option value='$r->id' " . ($r->id == $customerRegionId ? 'selected' : '') . " >" . $r->name . "</option>";
        }
    }
    $renderString .= "</select> . 
                            </td>" .
        "</tr>";
    echo $renderString;
    ?>
    <script>
        $(function () {
            $('#regionId2').bind('option', function () {
                if ($('#regionId2').html($(this).val().length === 0)) {
                    $('#regionId').val('');
                }
            });
        });
    </script>
    <?php
}

//-----------------------------------END----------------------------------------------
?>
<style>
    .struct_comp {
        font-size: 15px;
        padding: 15px 0 30px 5px;
        font-family: Arial, Tahoma sans-serif;
        float: left;
        width: 346px;
    }

    .img-comp {
        height: 40px;
        width: 50px;
        padding: -5px 30px 0 0;
        text-align: center;
        float: left;
    }
</style>
<?php

//-----------------------------------Главное и дочерние предприятие----------------------------------------------
function renderTrFindCompany($customer, $name, $id, $value, $readOnly)
{
    $parentName = 'Выберите главное предприятие';
    $customer && $customer->isChild() ? $nameMainComp = ' дочернее предприятие ' : $nameMainComp = ' главное предприятие ';
    $customer == null || $customer->isParent() ? $isVisibleMainCompany = 'hidden' : $isVisibleMainCompany = 'visible';
    $customer && $customer->isParent() ? $parentName = $customer->name : $parentName = '';
    $customer && $customer->isParent() ? $parentId = $customer->id : $parentId = '';
    $customer == null ? $isVisible = 'hidden' : $isVisible = 'visible';

    // VarDumper::dump($customer);
    $renderString = "<div><div class='struct_comp'><b>Структура компании: " . $nameMainComp . "</b></div>
    <div><img class='img-comp ttl contact_buttons' id='myModal'  data-id='<?= 2; ?>' src='/static/img/struktura.png' alt='' title='Древо компании' 
     style='cursor: pointer; visibility:" . $isVisible . "'>  </div>
    </div>" .
        "<tr class='line_in_form_table'>" .
        "<td class='cell_in_form_table left_cell_in_form_table'>" .
        "<div>
                  <b>" . $name . "</b>
        </div>" .
        "</td>" .
        "<td class='cell_in_form_table' >" .
        "<input  " . ($readOnly ? "readonly disabled" : "") . "  type='hidden' name='parentId' id='parentId'
          class='input_form_table' value=''>" .
        "<input " . ($readOnly ? "readonly disabled" : "") . " type='text' access='customer' name='parentName'
                 id='parentName' title='Выберите главное предприятие' class='input_form_table ttl' 
                 style='cursor: pointer; visibility: " . $isVisibleMainCompany . "' 
                 placeholder ='" . $parentName . "'>" .
        "</td>" .
        "</tr>";
    echo $renderString;
    ?>
    <script>
        $(function () {
            $('#parentName').bind('input', function () {
                if ($('#parentName').html($(this).val().length === 0)) {
                    $('#parentId').val('');
                }
            });
        });

        $(function () {
            $('#parentName').focus(function () {
                $(this).select();
            });
        });

        $(function () {
            $('#parentName').autocomplete(baseUrl + '/customer/AutocompleteCustomer/сustId/<?= $id ?>', {
                formatItem: function (data, i, n, value) {
                    return data[1];
                },
                formatResult: function (data, value) {
                    return data[1];
                },
                formatSelect: function (data) {
                    var param = data.data;
                    $('#parentId').val(param[0]);
                    return data;
                }
            });
        })
    </script>
    <?php
}

//-----------------------------------END----------------------------------------------


//-----------------------------------Ряды для контактных лиц---------------------------

/*Ряд добавления контакта*/
function renderTrContactHead($customerId)
{
//        $renderString = "<tr class='line_in_form_table'>" .
//                            "<td>" .
//                                "<div><b>Контакты</b></div>" .
//                            "</td>" .
//                            "<td>" .
//                                "<div></div>" .
//                            "</td>" .
//                        "</tr>" .
//                        "<tr class='line_in_form_table'>" .
//                            "<td>" .
//                                "<div><b>ФИО</b></div>" .
//                            "</td>" .
//                            "<td>" .
//                                "<div><b>Почта</b></div>" .
//                            "</td>" .
//                            "<td>" .
//                                "<div><b>Номер телефона</b></div>" .
//                            "</td>" .
//                        "</tr>";
//        <img src='/static/img/1uparrow.png' width='20px' height='20px' alt='Свернуть' title='Свернуть' class='ttl' onclick='toogle()' style='float: right'></div>
    $renderString =
        "<tr style='height: 50px'>" .
        "<td colspan='2'>" .
        "
                            <a id='add_new_blank' class='add_btn rc3 ddd fancy' href='/customer/contacts/id/" . $customerId . "/action/create' style='clear:both;'>
                                <span><img src='/static/img/plus_new.png' style='width: 10px; padding-top: 5px'></span>
                                <span>Добавить контакт<span class='ico'></span></span>
                            </a>" .
        "</td>" .
        "</tr>";
    echo $renderString;
}

//<img src='/static/img/plus3.png' width='25px' height='25px' style='padding-left: 10px' alt='Добавить контакт' title='Добавить контакт' class='ttl' onmouseover='this.src="/static/img/plus_new.png";' onmouseout='this.src="/static/img/plus3.png";'>     "<div><a href='/customer/contacts/id/" . $customerId . "/action/0' class='fancy'><span class='green_btn'> + Добавить контакт</span></a></div>" .

/*Ряды ввода ФИО и номеров телефона*/
//    function renderTrContacts($id, $fioValue, $phoneValue, $emailValue, $readOnly)
function renderTrContacts($contacts, $action)
{
    if ($contacts) {
        echo "<tr class='line_in_form_table'>" .
            "<table class='contact_table'>" .
            "<tr>" .
            "<th>" .
            "<div><b>ФИО</b></div>" .
            "</th>" .
            "<th>" .
            "<div><b>Должность</b></div>" .
            "</th>" .
            "<th>" .
            "<div><b>Телефон</b></div>" .
            "</th>" .
            "<th>" .
            "<div><b>Email</b></div>" .
            "</th>" .
            "<th>" .
            "<div><b>Примечания</b></div>" .
            "</th>" .
            "<th>" .
            "<div><b>Действия</b></div>" .
            "</th>" .
            "</tr>";
        foreach ($contacts as $contact) {
            if (!is_null($contact->name)) {
                echo "<tr class='child' id='trContact" . $contact->id . "'>" .
                    "<td class='fio'>" .
                    "<span id='fio" . $contact->id . "' style='font-size: 14px'><b>" . $contact->name . "</b></span>" .
//                                        "<input " . ($readOnly ? "readonly" : "") . " type='text' id='fio" . $id . "' class='input_form_table' value='" . $fioValue . "'>" .
                    "</td>" .
                    "<td class='position'>" .
                    "<span id='position" . $contact->id . "'>" . $contact->position . "</span>" .
                    "</td>" .
                    "<td class='phone'>" .
                    "<div id='phone" . $contact->id . "'>";
                $phonesArray = json_decode($contact->phone);
                if ($phonesArray) {
                    foreach ($phonesArray as $phoneNumber) {
                        echo "<span style='line-height: 1.5'>" . $phoneNumber . "</span><br>";
                    }
                }
                echo "</div>" .
//                                        "<input " . ($readOnly ? "readonly" : "") . " type='text' id='email" . $id . "' class='input_form_table' value='" . $emailValue . "'>" .
                    "</td>" .
                    "<td class='email'>" .
                    "<div id='email" . $contact->id . "'>";
                $emailsArray = json_decode($contact->email);
                if ($emailsArray) {
                    foreach ($emailsArray as $email) {
                        echo "<span style='line-height: 1.5'>" . $email . "</span><br>";
                    }
                }
                echo "</div>" .
//                                        "<input " . ($readOnly ? "readonly" : "") . " type='text' id='phone" . $id . "' class='input_form_table' value='" . $phoneValue . "'>" .
                    "</td>" .
                    "<td class='notes'>" .
                    "<div id='notes" . $contact->id . "'>";
                $notesArray = json_decode($contact->notes);
                if ($notesArray) {
                    foreach ($notesArray as $note) {
                        echo "<span style='line-height: 1.5'>" . $note . "</span><br>";
                    }
                }
                echo "</div>" .
                    "</td>";
                $fName = 'deleteContact';
                if ($action == 'edit') {
                    echo
                        "<td class='action_buttons'>" .
                        "<div><a href='/customer/contacts/id/" . $contact->id . "/action/1' class='fancy'><img src='/static/img/edit4.png' style='width: 25px; cursor: pointer' alt='Редактировать' title='Редактирвать' class='ttl contact_buttons'></a>
<img src='/static/img/delete_red.png' style='width: 25px; cursor: pointer; padding-left: 20px' alt='Удалить' title='Удалить' class='ttl contact_buttons' onclick='myConf(`Подтвердите действие`,`Вы уверены что хотите удалить контакт?`,`" . $fName . "`," . $contact->id . ")'>" .
                        "</td>";
                } else {
                    echo
                        "<td class='action_buttons'>" .
                        "<div><img src='/static/img/edit4.png' style='width: 25px; cursor: pointer; opacity: 0.25' alt='Недоступно' title='Недоступно' class='ttl'>
<img src='/static/img/delete_red.png' style='width: 25px; cursor: pointer; padding-left: 20px; opacity: 0.25;' alt='Недоступно' title='Недоступно' class='ttl'>" .
                        "</td>";
                }
                echo "</tr>";
            }
        }
        echo "</table>" .
            "</tr>";
    } else {
        echo "<tr class='line_in_form_table'><th><div style='text-align: center;height: 40px'>Контактных данных нет</div></th></tr>";
    }
//        echo $renderString;
}

//--------------------------------END-----------------------------------

//--------------------------Скрытые поля----------------------------------
function renderInputHidden($id, $value, $readOnly)
{
    $renderString = "<input " . ($readOnly ? "readonly" : "") . " type='hidden' id='" . $id . "' class='input_form_table' value='" . $value . "'>";
    echo $renderString;
}

//-------------------------------END--------------------------------

//---------------------Ряд с вводом данных и кнопкой поиска по адресу для карты----------------------
function renderTrWithButtonSearch($name, $id, $value, $readOnly)
{
    $renderString = "<tr class='line_in_form_table'>" .
        "<td class='cell_in_form_table left_cell_in_form_table'>" .
        "<div><b>" . $name . "</b></div>" .
        "</td>" .
        "<td class='cell_in_form_table'>" .
        "<input " . ($readOnly ? "readonly disabled" : "") . " type='text' id='" . $id . "' class='input_form_table' value='" . $value . "' style='width: 85%;'>" .
        "<img id='address_button' disabled='disabled' class='button_search_in_form_table ttl' src='/static/img/search-blue.png' title='Показать' style='cursor: pointer'>" .
        "</td>" .
        "</tr>";
    echo $renderString;
}

//-----------------------------END-----------------------------------

//-----------------------------Кнопки сохранения--------------------------------
function renderTrWithButtonSave($value)
{
    $renderString = "<tr class='line_in_form_table'>" .
        "<td colspan='2' class='cell_in_form_table' style='text-align: right'>" .
        "<input type='submit' id='save_customer' class='green_btn' style='float: none' value='" . $value . "'>" .
        "</td>" .
        "</tr>";
    echo $renderString;
}

//-------------------------------END--------------------------------

//--------------------------Ряды реквизитов------------------------------------
function renderTrRequisitesHead($customerId)
{
    $renderString = "<tr style='height: 50px'>" .
        "<td colspan='2'>" .
        "
                                    <a id='add_new_blank' class='add_btn rc3 ddd fancy' href='/customer/requisites/id/" . $customerId . "/action/create' style='clear:both;'>
                                        <span><img src='/static/img/plus_new.png' style='width: 10px; padding-top: 5px'></span>
                                        <span>Добавить реквизиты<span class='ico'></span></span>
                                    </a>" .
        "</td>" .
        "</tr>";
    echo $renderString;
}

function renderTrRequisites($requisites, $action)
{
    if ($requisites) {
        echo "<tr class='line_in_form_table'>" .
            "<table class='contact_table'>" .
            "<tr>" .
            "<th style='width: 5%'>" .
            "<div><b>№</b></div>" .
            "</th>" .
            "<th>" .
            "<div><b>Содержание</b></div>" .
            "</th>" .
            "<th style='width: 13%'>" .
            "<div><b>Действия</b></div>" .
            "</th>" .
            "</tr>";
        $num = 0;
        foreach ($requisites as $requisite) {
            echo "<tr class='child' id='trReq" . $requisite->id . "'>" .
                "<td class='id'>" .
                "<span id='id" . $requisite->id . "'>" . ++$num . "</span>" .
//                                        "<input " . ($readOnly ? "readonly" : "") . " type='text' id='fio" . $id . "' class='input_form_table' value='" . $fioValue . "'>" .
                "</td>" .
                "<td style='text-align: left;'>" .
                "<div style='line-height: 2' id='text" . $requisite->id . "'>" . $requisite->text . "</div>" .
                "</td>";
            $fNameDel = "delReq";
//                $fNameSave = "saveReq";
//                $fNameActive = "activateReq";
            if ($action == 'edit') {

                echo
                    "<td class='action_buttons'>" .
                    "
                                <a href='/customer/requisites/id/" . $requisite->id . "/action/edit' class='fancy' style='text-decoration: none'>
                                    <img src='/static/img/edit4.png' style='width: 25px; cursor: pointer' alt='Редактировать' title='Редактирвать' class='ttl contact_buttons'>
                                </a>
                                <img src='/static/img/delete_red.png' style='width: 25px; cursor: pointer; padding-left: 15px' alt='Удалить' title='Удалить' class='ttl contact_buttons' onclick='myConf(`Подтвердите действие`,`Вы уверены что хотите удалить реквизит?`,`" . $fNameDel . "`," . $requisite->id . ")'>" .
                    "</td>";
            } else {
                echo
                    "<td class='action_buttons'>" .
                    "
                            <img src='/static/img/edit4.png' style='width: 25px; cursor: pointer; opacity: 0.25' alt='Недоступно' title='Недоступно' class='ttl'>
                            <img src='/static/img/delete_red.png' style='width: 25px; cursor: pointer; padding-left: 20px; opacity: 0.25;' alt='Недоступно' title='Недоступно' class='ttl'>" .
                    "</td>";
            }
        }
        echo "</table>" .
            "</tr>";
    } else {
        echo "<tr class='line_in_form_table'><th><div style='text-align: center;height: 40px'>Реквизитов нет</div></th></tr>";
    }
}

//    function renderTrRequisites($requisites,$action)//
//    {
//        if($requisites) {
//            $renderString = "<ul id='sortable' class='ui-state-default'>";
//            foreach ($requisites as $requisite) {
//                $activeClass = false;
//                $activeTitle = false;
//                if($requisite->last == 1) {
//                    $activeClass = 'activePanel';
//                    $activeTitle = "title='Активный реквизит'";
//                }
//                $fNameDel = "delReq";
//                $fNameSave = "saveReq";
//                $fNameActive = "activateReq";
//                $renderString .=
//                    "<li class='req$requisite->id ui-state-default'>" .
//                        "<div class='reqGroup'>" .
//                        "<div class='reqButtonPanel ttl $activeClass'>";
//                if ($action == 'edit') {
//                    $renderString .=
//                            "<img class='reqButton delrb ttl' title='Удалить реквизиты' src='/static/img/dell2.png' onclick='myConf(`Подтвердите действие`,`Вы уверены что хотите удалить реквизит?`,`" . $fNameDel . "`," . $requisite->id . ")'>" .
//                            "<img class='reqButton editrb ttl' title='Сохранить реквизиты' src='/static/img/edit4.png' onclick='myConf(`Подтвердите действие`,`Вы уверены что хотите сохранить изменения реквизита?`,`" . $fNameSave . "`," . $requisite->id . ")'>";
////                            if($requisite->last == 0) {
////                                $renderString .=
////                                    "<img class='reqButton activerb ttl' title='Отметить как активный' src='/static/img/ok_blue.png' onclick='myConf(`ОБРАТИТЕ ВНИМАНИЕ!`,`Предыдущий активный реквизит будет заменён на выбранный, продолжить?`,`" . $fNameActive . "`," . $requisite->id . ")'>";
////                            }
//                } else {
//                    $renderString .=
//                            "<img class='reqButton delrb ttl' title='Недоступно' style='opacity: 0.25' src='/static/img/dell2.png'>" .
//                            "<img class='reqButton editrb ttl' title='Недоступно' style='opacity: 0.25' src='/static/img/edit4.png'>";
//                }
//                $renderString .=
//                        "</div>" .
//                        "<textarea class='reqText'>" . $requisite->text . "</textarea>" .
//                        "</div>" .
//                    "</li>";
//            }
//            $renderString .= "</ul>";
//        }else{
//            $renderString = "<div style='text-align: center'>Реквизитов нет</div>";
//        }
//        echo $renderString;
//    }
//-------------------------------END------------------------------------
?>
<!--------------------------------------------- END ------------------------------------------------->

<!-- Яндекс карты -->
<!--<script src="//api-maps.yandex.ru/2.1/?lang=ru_RU" type="text/javascript"></script>-->

<!-- Стили -->

<style>
    .main-data-section {
        width: 100%;
        display: grid;
        grid-template-columns: 35% 35% 1fr;
        grid-column-gap: 1rem;
    }

    .div_form_table {
        font-size: 14px;
    }

    /*-------------------COMMENTS----------------*/
    .customer-comments-section {
    }

    .customer-comments-section .customer-comments {
    }

    .customer-comments-section .time {
        float: left;
        width: 100%;
        color: #000;
        opacity: .5;
        font-size: 0.7em;
        text-align: left;
        padding-bottom: 5px;
        padding-right: 5px;
        clear: both;
    }

    .customer-comments-section .right {
        float: right;
    }

    .customer-comments-section .user-title, .user-create {
        background: #ffca11;
        padding: 0 10px;
        display: inline-block;
        margin-bottom: 5px;
        margin-right: 5px;
        border-radius: 5px;
        font-size: 16px;
        font-weight: 900;
        line-height: 28px;
    }

    .customer-comments-section .user-title a, .user-create a {
        color: #000;
        font-size: 13px;
        text-decoration: none;
    }

    .customer-comments-section .user-create {
        background: #3893c8;
    }

    .customer-comments-section .user-create a {
        color: #fff;
    }

    .customer-comments-section .triangle-right::after {
        content: "";
        position: absolute;
        top: 0px;
        bottom: 0px;
        right: -10px;
        border-width: 0 10px 10px 0;
        border-style: solid;
        border-color: inherit;
        display: block;
        width: 0;
    }

    .customer-comments-section .triangle-right {
        position: relative;
        margin-right: 10px;
        margin-left: 0px;
        border: none;
        border-top-color: currentcolor;
        border-right-color: currentcolor;
        border-bottom-color: currentcolor;
        border-left-color: currentcolor;
        border-radius: 8px;
        border-bottom-right-radius: 8px;
        border-bottom-right-radius: 0px;
        float: right;
        text-align: right;
        box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
    }

    .customer-comments-section .alert {
        background: #fff;
        background-color: rgb(255, 255, 255);
        color: #333;
        font-size: 13px;
        line-height: 16px;
        border: 1px solid #666;
        border-radius: 0px;
    }

    .customer-comments-section .triangle-left::after {
        content: "";
        position: absolute;
        top: 0px;
        left: -10px;
        border-width: 10px 0 0 10px;
        border-style: solid;
        border-color: #fff transparent;
        display: block;
        width: 0;
    }

    .customer-comments-section .triangle-left {
        position: relative;
        margin-left: 50px;
        border: none;
        border-radius: 8px;
        border-top-left-radius: 8px;
        border-top-left-radius: 0px;
        float: left;
        box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
    }

    .customer-comments-section .img-rounded {
        border: 1px solid #ccc !important;
        width: 40px;
        height: 40px;
        margin-left: 0px;
        float: left;
        border-radius: 20px;
        background-repeat: no-repeat;
        background-position: center top;
        background-attachment: fixed;
        background-size: cover;
    }

    .customer-comments-section .message {
        margin-right: 10px;
        margin-left: 10px;
        padding: 12px;
        opacity: 0.9;
        min-width: 12px;
        max-width: 300px;
        /*float: left;*/
        word-wrap: break-word;
    }

    .customer-comments-section .customer-comments li .time {
        margin-top: 15px !important;
    }

    /*-------------------END COMMENTS-----------------*/

    /*-------------------GOOGLE MAPS API-------------------*/
    .map {
        /*margin-left: 5px;*/
        height: 100%;
        border: 1px solid #696969;
        border-radius: 5px;
    }

    .controls {
        margin-top: 10px;
        border: 1px solid transparent;
        border-radius: 2px 0 0 2px;
        box-sizing: border-box;
        -moz-box-sizing: border-box;
        height: 32px;
        outline: none;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
    }

    #pac-input {
        background-color: #fff;
        /*font-family: Roboto;*/
        font-size: 15px;
        font-weight: 300;
        margin-left: 12px;
        padding: 0 11px 0 13px;
        text-overflow: ellipsis;
        width: 300px;
    }

    #pac-input:focus {
        border-color: #4d90fe;
    }

    .pac-container {
        /*font-family: Roboto;*/
    }

    #type-selector {
        color: #fff;
        background-color: #4d90fe;
        padding: 5px 11px 0px 11px;
    }

    #type-selector label {
        /*font-family: Roboto;*/
        font-size: 13px;
        font-weight: 300;
    }

    /*--------------------END GOOGLE MAPS--------------------*/

    .contact_table {
        width: 100%;
        /*max-width: 965px;*/
        /*max-width: 990px;*/
        border-bottom: solid 1px #b6b6b6;
    }

    .reqTable td {
        padding-left: 20px;
        max-width: 360px;
    }

    .notes {
        width: 20%;
    }

    .action_buttons {
        /*vertical-align: top;*/
    }

    .contact_table th {
        height: 35px;
        /*border-right:  solid 1px #b6b6b6;*/
        background-color: #ffc912;
        border-bottom: solid 1px #b6b6b6;
        text-align: center;
    }

    .contact_table td {
        height: 35px;
        /*border-right:  solid 1px #b6b6b6;*/
        border-bottom: solid 1px #b6b6b6;
        text-align: center;
    }

    /*.contact_buttons{*/
    /*padding-left: 5px;*/
    /*}*/

    tr.child {
        height: 45px;
    }

    tr.child:nth-child(even) {
        background-color: #f1f1f1;
    }

    tr.child:nth-child(odd) {
        background-color: #f9f9f9;
    }

    tr.child:nth-child(odd):hover {
        background-color: #dfdfdf;
    }

    tr.child:nth-child(even):hover {
        background-color: #dfdfdf;
    }

    .form_table {
        width: 100%;
    }

    .form_table td {
        height: 40px;
    }

    .line_in_form_table {
        border-bottom: solid 1px #b6b6b6;
    }

    .cell_in_form_table {
        padding: 5px;
        vertical-align: middle;
    }

    .left_cell_in_form_table {
        width: 25%;
        border-right: solid 1px #b6b6b6;
    }

    .input_form_table {
        border-radius: 5px;
        height: 20px;
        width: 98%;
        padding: 5px 8px;
        float: left;
        font-size: 14px;
    }

    .input_form_table:focus {
        border: 1px solid cornflowerblue;
    }

    .button_search_in_form_table {
        width: 25px;
        height: 25px;
        margin: 5px 15px;
    }

    .green_background {
        background: #a2ffab;
    }

    .red_background {
        background: #ff3f44;
    }

    .comments {
        border: 1px solid #696969;
        border-radius: 5px;
        margin-left: 10px;
        padding: 5px;
        float: left;
        width: 20%;
    }

    .comment {
        border: 1px solid #c5c5c5;
        background-color: #e5e5e5;
        margin: 5px;
        border-radius: 5px;
        padding: 20px;
    }

    .userCreated_comment {
        float: right;
        color: #7a7a7a;
        font-style: normal;
        font-size: 10px;
    }

    .date_comment {
        float: right;
        color: #7a7a7a;
        font-style: normal;
        font-size: 10px;
    }

    .comment_comment {

    }

    .add_comment_form {
        border: 1px solid #c5c5c5;
        background-color: #e5e5e5;
        margin: 5px;
        border-radius: 5px;
        padding: 5px;
        text-align: center;
    }

    .add_comment_text {
        width: 95%;
        height: 50px;
        resize: none;
        padding: 5px;
        border-radius: 5px;
    }

    .add_button {
        width: 100%;
        text-align: center;
    }

    #add_new_blank { /*КНОПКИ ДОБАВИТЬ РЕКВИЗИТЫ И КОНТАКТЫ*/
        margin-bottom: 20px;
    }

    #sortable {
        list-style-type: none;
        /*width: 100%;*/
        border: none
    }

    #sortable li {
        /*margin: 3px 3px 3px 0;*/
        /*padding-left: 25px;*/
        /*padding-bottom: 15px;*/
        margin-left: 100px;
        margin-bottom: 15px;
        margin-top: 2px;
        float: left;
        width: 400px;
        height: 140px;
        border: 1px solid grey;
        border-radius: 5px;
    }

    .reqGroup .reqText { /*TEXTAREA*/
        resize: none;
        width: 390px;
        height: 100px;
        border-radius: 5px;
        border: none;
        padding: 5px;
        padding-top: 32px;
        line-height: 1.5;
    }

    .reqGroup .reqButton {
        /*position: absolute;*/
        width: 20px;
        height: 20px;
        cursor: pointer;
        /*right: 100px;*/
    }

    .reqGroup .editrb {
        padding-right: 5px;
        padding-top: 5px;
        float: right;
    }

    .reqGroup .delrb {
        padding-left: 5px;
        padding-top: 5px;
    }

    .reqGroup .activerb {
        padding-right: 5px;
        padding-top: 5px;
        float: right;
    }

    .reqGroup .reqButtonPanel {
        position: absolute;
        width: 400px;
        height: 30px;
        background-color: #ffc912;
        border-top-left-radius: 5px;
        border-top-right-radius: 5px;
    }

    /*.activePanel.reqButtonPanel{*/
    /*background-color: #44ff52;*/
    /*}*/

    /*----------------- WEBKIT ------------------*/
    .reqGroup, .contact_buttons {
        -webkit-transition: all 0.3s ease;
        -moz-transition: all 0.3s ease;
        -o-transition: all 0.3s ease;
        transition: all 0.3s ease;
    }

    .contact_buttons:hover {
        /*box-shadow: inset 0 0 0 2px #525252;*/
        -webkit-transform: scale(1.1);
        -ms-transform: scale(1.1);
        transform: scale(1.1);
    }

    /*.reqText:hover {*/
    /*-webkit-transition: all 0.4s ease;*/
    /*-moz-transition: all 0.4s ease;*/
    /*-o-transition: all 0.4s ease;*/
    /*transition: all 0.4s ease;*/
    /*!*background: #ebdbcb;*!*/
    /*background: #dfdfdf;*/
    /*}*/

    .reqButton:hover {
        -webkit-transform: scale(1.2);
        -ms-transform: scale(1.2);
        transform: scale(1.2);
        -webkit-transition: all 0.3s ease;
        -moz-transition: all 0.3s ease;
        -o-transition: all 0.3s ease;
        transition: all 0.3s ease;

    }

    /*------------------ END --------------------*/

</style>
<!-- Вкладки -->
<a href="/customer/" class="green_btn">Возврат к базе Заказчиков</a>
<? if ($action == 'show'): ?>
    <? if (Tools::checkAccess('CUSTOMER', 'customer', 'edit')): ?>
        <a href="/customer/edit/id/<?= $customer->id; ?>" class="green_btn">Редактирование</a>
    <? endif; ?>
<? endif; ?>
<div class="clear"></div>

<? switch ($action): ?>
<? case 'create': ?>
        <? break; ?>
    <? default: ?>
        <div>
            <ul class="tabs_links clearfix">
                <li class="active"><a href="/customer/<?= $action; ?>/id/<?= $customer->id; ?>">Карточка заказчика</a>
                </li>
                <!--                --><? // if (Tools::checkAccess('CUSTOMER', 'customer_history', 'edit')) :?>
                <!--                    <li><a href="/customer/history/id/--><? //=$customer->id;?><!--/action/-->
                <? //=$action;?><!--">История обращений</a></li>-->
                <!--                --><? //endif;?>
                <!--                <li><a href="#">План работ с клиентом</a></li>-->
            </ul>
        </div>
        <? break; ?>
    <? endswitch; ?>

<div class="myConf"></div><!-- CONF AND PROMT -->
<!----------------------------------------- НАЗВАНИЯ И АДРЕСА ------------------------------------------------->
<div class="main-data-section">
    <div class="div_form_table">
        <table id="table_info" class="form_table">
            <?
            $criteriaHolding = new CDbCriteria();
            $criteriaHolding->addCondition('isDell = 0');
            if ($customer && $customer->holdingId) {
                $criteriaHolding->addCondition('id != ' . $customer->holdingId);
            }
            $holdings = CustomerHoldings::model()->findAll($criteriaHolding);
            if ($customer && $customer->holdingId) {
                $criteriaHolding->addCondition('id != ' . $customer->holdingId);
            }
            $regions = UserRegion::model()->findAll();

            $readOnlyInputs = ($action && $action == "show") ? true : false;

            renderTr("Наименование краткое (русский)", "name", ((isset($customer) && $customer->name) ? $customer->name : ""), $readOnlyInputs, 250);

            renderTr("Наименование (русский)", "nameRus", ((isset($customer) && $customer->nameRus) ? $customer->nameRus : ""), $readOnlyInputs, 250);

            renderTr("Наименование (украинский)", "nameUkr", ((isset($customer) && $customer->nameUkr) ? $customer->nameUkr : ""), $readOnlyInputs, 250);

            renderTr("Наименование (английский)", "nameEn", ((isset($customer) && $customer->nameEn) ? $customer->nameEn : ""), $readOnlyInputs, 250);

            renderTr("Юридический адрес", "lawAdres", ((isset($customer) && $customer->lawAdres) ? $customer->lawAdres : ""), $readOnlyInputs, 120);

            renderTrWithButtonSearch("Фактический адрес", "address", ((isset($customer) && $customer->adres) ? $customer->adres : ""), $readOnlyInputs);

            renderTr("Область, страна", "region", ((isset($customer) && $customer->region) ? $customer->region : ""), $readOnlyInputs, 1000);
            //        renderTrWithButtonSearch("Фактический адрес", "address", ((isset($customer) && $customer->adres) ? $customer->adres : ""), $readOnlyInputs);
            //        renderTr("Расстояние, км", "distance", ((isset($customer) && $customer->distance) ? $customer->distance : ""), true);
            if ($customer && $customer->isChild()) {
                renderTr("Код по ЕГРПОУ", "egrpou", ((isset($customer) && $customer->parent->egrpou) ? $customer->parent->egrpou : $customer->egrpou), $readOnlyInputs, 30);
            } else {
                renderTr("Код по ЕГРПОУ", "egrpou", ((isset($customer) && $customer->egrpou) ? $customer->egrpou : ""), $readOnlyInputs, 30);
            }

            renderTr("ИНН", "inn", ((isset($customer) && $customer->inn) ? $customer->inn : ""), $readOnlyInputs, 30);

            renderTr("Номер НДС", "nds", ((isset($customer) && $customer->nds) ? $customer->nds : ""), $readOnlyInputs, 30);

            renderTrHolding("Холдинг", "holding", isset($customer->holding) ? $customer->holding : false, $holdings, $readOnlyInputs);

            //if (Tools::checkAccess('customer', 'editRegionId', 'edit')) {
            renderTrRegion("Регион", "regionId", isset($customer->regionId) ? $customer->regionId : false, $regions, $readOnlyInputs);
            // }
            renderTrFindCompany(($customer ? $customer : null), "Изменение структуры", ((isset($customer) && $customer->id) ? $customer->id : 0), ((isset($customer) && $customer->name) ? $customer->name : ""), $readOnlyInputs);

            renderInputHidden("coord1", ((isset($customer) && $customer->coord1) ? $customer->coord1 : ""), true);

            renderInputHidden("coord2", ((isset($customer) && $customer->coord2) ? $customer->coord2 : ""), true);

            if ($action && $action != "show") renderTrWithButtonSave(($action && $action == "edit") ? "Сохранить" : "Создать");
            //        renderTrContactHead();
            //        foreach($customer->contacts as $contact){
            //            renderTrContacts($contact->id, $contact->name, $contact->phone, $contact->email, $readOnlyInputs);
            //        }
            ?>
        </table>
    </div>
    <!------------------------------------------ END ----------------------------------------------->

    <!----------------------- GOOGLE MAP ---------------------------->

    <div id="map" class="map"></div>

    <!------------------------ END GOOGLE MAP ------------------------->

    <? if (in_array($action, ['edit', 'show'])) : ?>
        <div class="customer-comments-section" style="height: 700px">
            <table width="100%" class="customer-comments">
                <td style="padding: 0 5px;">
                    <div style="padding: 10px; background-color: #F3F5F5; box-shadow: rgba(0, 0, 0, 0.02) 0 0 0 1px,
                    rgba(0, 0, 0, 0.05) 0 1px 2px 0, rgba(0, 0, 0, 0.05) 0 2px 8px 0; height: 770px">
                        <div class="block" style="height: 600px;overflow-y: scroll;padding: 10px;">
                            <ul>
                                <?php
                                if (!empty($customer->comments)) {
                                    foreach ($customer->comments as $comment) {
                                        $check = ($comment->userId == Yii::app()->user->id) ? true : false;

                                        $foto = $comment->user->kadry->foto;
                                        if (!file_exists(Yii::getPathOfAlias('webroot') . '/static/uploads/ok/' . $foto)) $foto = 'nophoto.jpg';
                                        $temp_css = 'border-color: rgba(231,95,32,0.08) transparent; background-color: rgba(231,95,32,0.08);';
                                        ?>
                                        <li>
                                            <div class="time">
                                            <span class="<?= ($check) ? 'right' : 'left'; ?>">
                                                            <b><?= $comment->user->title; ?></b>
                                                        (<?= date('d.m.Y H:m', strtotime($comment->data)); ?>)
                                                        </span>
                                            </div>
                                            <div style="clear: both;"></div>
                                            <div class="avatar <?= ($check) ? 'right' : 'left'; ?>">
                                                <div class="img-rounded"
                                                     style="background: #f4f4f4 url(/static/uploads/ok/<?= $foto ?>);
                                                             background-repeat: no-repeat;
                                                             background-position: center center;
                                                             background-attachment: inherit;
                                                             background-size: cover;
                                                             "></div>
                                            </div>
                                            <div class="message left alert triangle-<?= ($check) ? 'right' : 'left'; ?>"
                                                 style="<?= ($check) ? $temp_css : ''; ?>">
                                                <?= $comment->comment; ?>

                                            </div>
                                        </li>
                                    <?php }
                                } else {
                                    //                                echo "<p style='color: red;'>Комментарий оставлено не было!!!</p>";
                                    echo '';
                                } ?>
                                <li>
                                    <div style="clear: both;"></div>
                                </li>
                            </ul>
                            </ul>
                        </div>
                        <div style="margin-top: 105px">
                            <form class="send-message" method="post">
                                <textarea name="comment" class="form-control comment-textarea"
                                          style="width: 65%; padding: 10px; resize:none; box-shadow: none; border: 1px solid #aaa !important;"
                                          rows="2"
                                          placeholder="Напишите комментарий..."></textarea>
                                <a onclick="addCommentCustomer(<?= $customer->id ?>)" class="green_btn panel_btn"
                                   style="float: right; cursor: pointer;">Добавить
                                </a>
                            </form>
                        </div>
                    </div>
                </td>
            </table>
        </div>
    <? endif; ?>
</div>

<?
//------------------------Кнопка добавить контакты при создании закащика -----------------
$action == 'edit' || $action == 'show' ? $visible = 'hidden' : $visible = 'visible';
$action == 'edit' || $action == 'show' ? $custId = $customer->id : $custId = 1;
//VarDumper::dump($custId);
?>

<!-----------------------Новая форма при создании ----------------------------->
<div style="display: block; padding-top: 0px; line-height: 1px; visibility: <?= $visible ?> " type=' <?= $visible ?> '>
    <h2 style="padding-bottom: 10px;">Контактная информация</h2>
    <table class="form_table">
        <tbody>
        <tr style="height: 50px">
            <td colspan="2" class="form_table td">
                <div class="createButtonSection">

                    <a id="add_new_blank" class="add_btn rc3 ddd fancy1"
                       href="/customer/contacts/id/<?= $custId ?>/action/create"
                       style="clear:both; margin-bottom: 20px;">
                        <span><img src="/static/img/plus_new.png" style="width: 10px; padding-top: 5px"></span>
                        <span>Добавить контакт<span class="ico"></span></span>
                    </a>

                </div>
                <div id="myContact">
                    <table class='contact_table' id='tableContact'>
                        <tr class='line_in_form_table'>
                            <th>
                                <div><b>ФИО</b></div>
                            </th>
                            <th>
                                <div><b>Должность</b></div>
                            </th>
                            <th>
                                <div><b></b></div>
                            </th>
                            <th>
                                <div><b>Телефон</b></div>
                            </th>
                            <th>
                                <div><b>Email</b></div>
                            </th>
                            <th>
                                <div><b>Примечания</b></div>
                            </th>
                            <th>
                                <div><b>Действия</b></div>
                            </th>
                        </tr>
                    </table>
                </div>
                <div style="margin-top: 100px"></div>
            </td>
        </tr>
        </tbody>
    </table>
</div>


<? //build H2
$h2Req = false;
$h2Contact = false;
if ($action == 'edit' or $action == 'show') {
    if (Tools::checkAccess('CUSTOMER', 'customer_requisites', 'edit') or
        Tools::checkAccess('CUSTOMER', 'customer_requisites', 'view') or
        Tools::checkAccess('CUSTOMER', 'customer_requisites_create', 'edit')) {
        $h2Req = 'Реквизиты';
    }
    if (Tools::checkAccess('CUSTOMER', 'customer_contact', 'edit') or
        Tools::checkAccess('CUSTOMER', 'customer_contact', 'view') or
        Tools::checkAccess('CUSTOMER', 'customer_contact_create', 'edit')) {
        $h2Contact = 'Контактная информация';
    }
}
?>

<div class="clear" style="height: 10px;"></div>

<!---------------------------------------- КОНТАКТЫ ------------------------------------------>
<div style="margin-top: -250px">
    <table class="form_table">
        <!--        <h2>Контактная информация</h2>-->
        <h2><?= $h2Contact ? $h2Contact : ''; ?></h2>
        <?
        if ($action == 'edit' or $action == 'show') {
            if (Tools::checkAccess('CUSTOMER', 'customer_contact_create', 'edit')) {
                renderTrContactHead($customer->id);
            }
            if (Tools::checkAccess('CUSTOMER', 'customer_contact', 'edit')) {
                renderTrContacts($customer->contacts, 'edit');
            } elseif (Tools::checkAccess('CUSTOMER', 'customer_contact', 'view')) {
                renderTrContacts($customer->contacts, 'show');
            }
        }
        ?>
    </table>
</div>
<!------------------------------------------ END ---------------------------------------------->


<!--<div class="clear" style="height: 20px;"></div>-->

<!---------------------------------------- РЕКВИЗИТЫ ------------------------------------------>
<script type="text/javascript" src="<?= Yii::app()->baseUrl ?>/static/js/jquery-ui.js"></script>
<div style="margin-bottom: 50px">
    <table class="form_table">
        <!--        <h2>Реквизиты</h2>-->
        <h2><?= $h2Req ? $h2Req : ''; ?></h2>
        <?
        if ($action == 'edit' or $action == 'show') {
            if (Tools::checkAccess('CUSTOMER', 'customer_requisites_create', 'edit')) {
                renderTrRequisitesHead($customer->id);
            }
        }
        ?>
    </table>

    <?
    if ($action == 'edit' or $action == 'show') {
        if (Tools::checkAccess('CUSTOMER', 'customer_requisites', 'edit')) {
            renderTrRequisites($customer->requisites, 'edit');
        } elseif (Tools::checkAccess('CUSTOMER', 'customer_requisites', 'view')) {
            renderTrRequisites($customer->requisites, 'show');
        }
    }
    ?>
</div>
<script>
    //    $( function() {
    //        $( "#sortable" ).sortable();
    //        $( "#sortable" ).disableSelection();//БЛОКИ РЕКЗВИЗИТОВ
    //    } );
</script>
<!------------------------------------------ END ---------------------------------------------->

<!-- Комментарии -->
<!--<div id="comments" class="comments">-->
<!--    --><? //
//    if(isset($customer) && isset($customer->comments) && $customer->comments){
//        foreach ($customer->comments as $comment){ ?>
<!--            <div id="--><? //= $comment->id ?><!--" class="comment">-->
<!--                <div class="comment_comment">--><? //= $comment->comment ?><!--</div> <br>-->
<!--                <div class="userCreated_comment">--><? //= $comment->user->getKadryFioShort()?><!--</div><br>-->
<!--                <div class="date_comment">--><? //= $comment->data ?><!--</div>-->
<!--            </div>-->
<!--        --><? // }
//    } else { ?>
<!--        <div class="comment" id="non_comments"> Комментариев нету </div>-->
<!--    --><? // }
//    if ($action == "edit") { ?>
<!--        <div id="add_comment" class="add_comment_form">-->
<!--            <textarea class="add_comment_text" type="text" ></textarea>-->
<!--        </div>-->
<!--        <div class="add_button">-->
<!--            <input id="add_comment_button" type="button" onclick="" class="green_btn" style="float: none" value="Добавить комментарий">-->
<!--        </div>-->
<!--    --><? // } ?>
<!--</div>-->

<!-- Сохранение комментариев -->
<script>
    <? if ($action && $action == "edit") { ?>
    $('#add_comment_button').click(function () {
        if ($('.add_comment_text').val() != "") {
            $.post('<?php echo $this->createUrl('SaveComment')?>',
                {
                    idCustomer: <?= $customer->id ?>,
                    comment: $('.add_comment_text').val(),
                }, function (data) {
                    data = $.parseJSON(data);
                    if (data['status'] == "OK") {
                        $('#non_comments').remove();
                        $('.comments').find('.add_comment_form').before(data['comment']);
                        $('.add_comment_text').val("");
                    } else {
                        showErrors(data['error']);
                    }
                });
        } else {

        }
    }); <? } ?>
</script>

<!-- Вспомогательные функции -->
<script>
    //    function showErrors(msg){
    //        var elem = "" +
    //            "<tr id='error' class='error'>" +
    //            "<td class='td_form_customer' colspan='2' style='text-align: center; background-color: #ff3f44; height: 30px;'>"
    //            + msg +
    //            "</td>" +
    //            "</tr>";
    //        $('#table_info').find("tr.error").hide();
    //        $('#table_info').find("tr.info").hide();
    //        $('#table_info').append(elem);
    //    }
    //
    //    function showInfo(msg){
    //        var elem = "" +
    //            "<tr id='info' class='info'>" +
    //            "<td class='td_form_customer' colspan='2' style='text-align: center; background-color: #5fff54; height: 30px;'>"
    //            + msg +
    //            "</td>" +
    //            "</tr>";
    //        $('#table_info').find("tr.error").hide();
    //        $('#table_info').find("tr.info").hide();
    //        $('#table_info').append(elem);
    //    }

    //-- Скрыть/показать контакты ---
    function toogle() {
//        switch (type){
//            case 'contact_table':
        if ($('.contact_table').hasClass('display')) {
            $('.contact_table').removeClass('display').fadeIn('slow');
        } else {
            $('.contact_table').addClass('display').fadeOut('slow');
        }
//                break;
//            default:
//                break;
//        }
    }
</script>

<!-- Сохранение/Добавление заказчика -->
<script>
    let contacts = [];

    <? if ($action && $action != "show"){ ?>
    $('#table_info').change(function (event) {
        $('#' + event.target.id).removeClass('no_value');
    });

    $('#save_customer').click(function () {
        var ok = true;

        $('#table_info').find('.input_form_table').each(function (i) {
//            if($(this).is('#name') || $(this).is('#fio') || $(this).is('#region') || $(this).is('#address') || $(this).is('#distance') || $(this).is('#coord1') || $(this).is('#coord2') || $(this).is('#egrpou')) {
            if ($(this).is('#name') || $(this).is('#egrpou')) {
                if ($(this).val() === "") {//if ($(this).val() == "") {
                    $(this).removeClass('green_background');
                    $(this).addClass('red_background');
                    ok = false;
                }
            }
        });

        $('#table_info').find('.input_form_table').each(function (i) {
            if ($(this).is('#regionId')) {
                if ($(this).val() === "0") {//if ($(this).val() == "0") {
                    $(this).removeClass('green_background');
                    $(this).addClass('red_background');
                    ok = false;
                }
            }
        });

        if (ok) {
            $.blockUI();
            $.post('<?php echo $this->createUrl('Save')?>',
                {
                    <? if ($action && $action == "edit"){ ?>
                    id: <?= $customer->id ?>,
                    <? } ?>
                    name: $('#name').val(),
                    region: $('#region').val(),
                    regionId: $('#regionId').val(),
                    address: $('#address').val(),
                    lawAdres: $('#lawAdres').val(),
                    egrpou: $('#egrpou').val(),
                    inn: $('#inn').val(),
                    nds: $('#nds').val(),
                    nameUkr: $('#nameUkr').val(),
                    nameEn: $('#nameEn').val(),
                    nameRus: $('#nameRus').val(),
                    holding: $('#holding').val(),
                    parentId: $('#parentId').val(),
                    contacts: JSON.stringify(contacts)
                },

                function (data) {
                    data = $.parseJSON(data);
                    if (data['status'] === "OK") {
                        $.unblockUI();
                        <!-- Выподим предууприждение об оюязательном вводе контактов -->
                        let contact_table = document.getElementsByClassName('contact_table');
                        if (!contact_table) {
                            alert("\nЗАПОЛНИТЕ КОНТАКТНУЮ ИНФОРМАЦИЮ. " + "\n" + "\n"
                                + "Обязательные для заполнения поля: " + "\n"
                                + "ФИО, должность, телефон!");
                        }
                        <!-- Выподим предууприждение если контакты не заполнены -->
                        let fio1 = document.getElementsByClassName("fio");
                        let phone1 = document.getElementsByClassName("phone");
                        let position1 = document.getElementsByClassName("position");

                        if ((fio1.length && fio1[0].getElementsByTagName("span")) &&
                            (phone1.length && phone1[0].getElementsByTagName("div")[0].firstChild) &&
                            (position1.length && position1[0].getElementsByTagName("span")[0].firstChild)) {
                            console.log(fio1);
                            console.log(phone1);
                            console.log(position1);
                        } else {
                            alert("\nЗАПОЛНИТЕ КОНТАКТНУЮ ИНФОРМАЦИЮ. " + "\n" + "\n"
                                + "Обязательные для заполнения поля: " + "\n"
                                + "ФИО, должность, телефон!");
                        }

                        sendNotify('Операция успешна.', 'success');
                        <? if($action && $action == "edit") { ?>
                        location.href = <? $this->createUrl('edit') ?>  +data['id'];
                        <?}elseif($action == "create"){?>
                        location.href = '/customer/edit/id/' + data['id'];
                        <?}?>
                    } else if (data['status'] === "NO") {
//                        $.unblockUI();
//                        showErrors(data['error']);
                        if (data['requestEdit']) {
                            if (data['create']) {
                                okRedir = function (redirId) {
                                    location.href = '/customer/edit/id/' + redirId;
                                };
                                // myConf(['Сообщение системы', 'Такой ЕГРПОУ код уже есть, редактировать существуещего заказчика?', 'Нет', 'Да'], ['', data['requestId']]);
                                sendNotify('Такой ЕГРПОУ код уже существует! Проверьте правильность ввода данных и повторите попытку!', 'error');
                                return false;
                            } else {
                                okMerge = function (mainId, checkId) {
                                    if (mainId) {
                                        $.post('<?php echo $this->createUrl('MergeCustomer')?>',
                                            {
                                                mainId: mainId,
                                                checkId: checkId
                                            }
                                            , function (data2) {
                                                data2 = $.parseJSON(data2);
                                                if (data2['success']) {
                                                    $.unblockUI();
                                                    sendNotify('Операция успешно завершена', 'success');
                                                    location.href = '/customer/edit/id/' + mainId;
                                                } else {
                                                    $.unblockUI();
                                                    sendNotify(data2['error'], 'error');
                                                }
                                            }
                                        );
                                    } else {
                                        $.unblockUI();
                                        sendNotify('Утерян идентификатор', 'error');
                                    }
                                };
                                myConf(['Сообщение системы', 'Такой ЕГРПОУ код уже есть,объединить данные текущего заказчика с данными существующего заказчика?', 'Да', 'Нет'], ['okMerge', data['requestCheckId'], data['requestId']]);

                            }
                        } else {
                            $.unblockUI();
                            sendNotify(data['error'], 'error');
                            if(contacts.length > 0) {
                                setTimeout(function () {
                                    location.reload(true);
                                }, 3000);
                            }else {
                                console.log('BBB');
                            }
                        }
                    }
                }
            );
        }
    });
    <? } ?>
</script>
<!-- Сохранение/Удаление реквизитов -->
<script>

    function saveReq(id) {
        $('.__holder').hide();
        $.blockUI();
        var error = false;

        if (!id) {
            error = true;
            $.unblockUI();
            sendNotify('Утерян индетификатор', 'error');
        }
        var text = $('.req' + id).find('.reqText').val();
        if (text.length <= 0) {
            error = true;
            $.unblockUI();
            sendNotify('Невозможно сохранить пустой текст', 'error');
        }

        if (!error) {
            $.post('<?php echo $this->createUrl('SaveRequisite')?>',
                {id: id, text: text, action: 'edit'},
                function (data) {
                    data = $.parseJSON(data);
                    if (data.success) {
                        $('.req' + id).find('.reqText').html(data.conf);
                        $.unblockUI();
                        sendNotify('Операция успешна.', 'success');
                    } else {
                        $.unblockUI();
                        sendNotify(data.error, 'error');
                    }
                }
            );
        }
    }

    function delReq(id) {
        $('.__holder').hide();
        $.blockUI();
        if (id) {
            $.post('<?php echo $this->createUrl('DeleteRequisite')?>',
                {id: id},
                function (data) {
                    data = $.parseJSON(data);
                    if (data.success) {
//                        $('.req' + id).remove();
                        $('#trReq' + id).remove();
                        $.unblockUI();
                        sendNotify('Операция успешна.', 'success');
                    } else {
                        $.unblockUI();
                        sendNotify(data.error, 'error');
                    }
                });
        } else {
            $.unblockUI();
            sendNotify('Утерян индетификатор', 'error');
        }
    }

</script>
<!-- УСТАНОВИТЬ АКТИВНЫЙ РЕКВИЗИТ -->
<script>

    function activateReq(id) {
        $('.__holder').hide();
        $.blockUI();
        var error = false;
        if (!id) {
            error = true;
            $.unblockUI();
            sendNotify('Утерян индетификатор', 'error');
        }

        $.post('<?php echo $this->createUrl('SetActiveRequisite')?>',
            {id: id,},
            function (data) {
                data = $.parseJSON(data);
                if (data.success) {
//                    $('#trContact' + id).remove();
                    $(".activePanel").removeClass("activePanel");
                    $('.req' + id).find(".reqButtonPanel").addClass("activePanel").find(".activerb").remove();
                    $.unblockUI();
                    sendNotify('Операция успешна.', 'success');
                } else {
                    $.unblockUI();
                    sendNotify(data.error, 'error');
                }
            }
        );
    }

</script>

<!-- Удаление контактов -->
<script>

    function deleteContact(id) {
        $('.__holder').hide();
        $.blockUI();
        var error = false;
        if (!id) {
            error = true;
            $.unblockUI();
            sendNotify('Утерян индетификатор', 'error');
        }

        if (!error) {
            $.post('<?php echo $this->createUrl('DeleteContact')?>',
                {id: id,},
                function (data) {
                    data = $.parseJSON(data);
                    if (data.success) {
                        $('#trContact' + id).remove();
                        $.unblockUI();
                        sendNotify('Операция успешна.', 'success');
                    } else {
                        $.unblockUI();
                        sendNotify(data.error, 'error');
                    }
                }
            );
        }
    }
</script>

<script>

    let section = $('.customer-comments-section');
    let commentBlock = $(section).find('.block');

    if (commentBlock.length > 0) {
        $(commentBlock).animate({
            scrollTop: ($(commentBlock)[0].scrollHeight)
        }, 800);
    }

    function addCommentCustomer(customerId) {
        $.blockUI();
        let textArea = $(section).find('.comment-textarea');
        var text = $.trim($(textArea).val());
        if (text.length <= 0) {
            sendNotify('Невозможно отправить пустой комментарий.', 'error');
            $.unblockUI();
            return false;
        }

        $.post("/customer/SaveComment", {idCustomer: customerId, comment: text}, function (data) {
            var data = $.parseJSON(data);
            $(textArea).val('');
            if (data.status) {
                let commentsSection = $(section).find('.customer-comments');
                let commentsList = $(commentsSection).find('ul');
                $(commentsList).append(data.comment);
                $(commentBlock).animate({
                    scrollTop: ($(commentBlock)[0].scrollHeight)
                }, 800);
                $.unblockUI();
                sendNotify('Комментарий успешно добавлен.', 'success');
            } else {
                $.unblockUI();
                sendNotify(data.error, 'error');
            }
        });
    }
</script>

<!-- Поиск -->
<!--<script>-->
<!--    $('#region').keyup(function(event){-->
<!--        if(event.keyCode==13)-->
<!--        {-->
<!--            $('#address').focus();-->
<!--        }-->
<!--    });-->
<!--    $('#address').keyup(function(event){-->
<!--        if(event.keyCode==13)-->
<!--        {-->
<!--            $('.button_search_in_form_table').click();-->
<!--        }-->
<!--    });-->
<!--    $('.button_search_in_form_table').click(function () {-->
<!--        map.searchAddress($('#region').val() + ", " + $('#address').val());-->
<!--    });-->
<!--</script>-->

<!-- При загрузке страницы-->
<script>
    //    $('.contact_table').hide().addClass('display');

    //    $(function () {
    //        ymaps.ready(function () {
    //
    //            map = new Map($('#region'), $('#address'), $('#distance'), $('#coord1'), $('#coord2'));
    //
    //            <?php //if(($customer && $customer->coord1 != null) || ($customer && $customer->coord2 != null)) {?>
    //            map.showByCoordinates(<?//= $customer->coord1 ?>//, <?//= $customer->coord2 ?>//);
    //            <?php //} elseif ($customer && $customer->adres != null){?>
    //            map.showByAddress("<?//=$customer->region ?>//, " + " <?//=$customer->adres ?>//");
    //            <?php //} else {?>
    ////            showErrors("Не указаны адресс и координаты!");
    //            map.firstSearch = false;
    //            <?php //}?>
    //            $('#address_button').prop( "disabled", false );
    //            $('#save_customer').prop( "disabled", false );
    //        });
    //    });

    //--------- FANCY BOX Древо компании -------------

    $(document).ready(function () {
        $('#myModal').fancybox({
            href: '/Customer/ShowModalChildCustomer/id/  <?= $customer ? $customer->id : 0?>',
            'modal': false,
            'autoScale': true,
            'transitionIn': 'none',
            'transitionOut': 'none',
            'scrolling': false,
            'autoSize': true,
            'closeClick': false,
            'fitToView': true,
            'showCloseButton': true,
            'openEffect': true,
            'closeEffect': true,
            'type': 'inline',
            'helpers': {
                'overlay': {
                    'locked': false
                }
            }
        });
        $(".fancy").fancybox({
            autoSize: true,
            type: 'ajax',
            timeout: 5000,
            hideOnOverlayClick: false,
            showCloseButton: false,
            onClosed: function () {
                $.blockUI();
                location.reload();
            },
            "onComplete": function () {
            }
        });
        $(".fancy1").fancybox({
            autoSize: true,
            type: 'ajax',
            timeout: 5000,
            hideOnOverlayClick: false,
            showCloseButton: false,
            onClosed: function () {
            },
            "onComplete": function () {
            }
        });
    });
</script>

<!---------------------- GOOGLE MAP ---------------------------->
<script async defer
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCn1jQ1bQysM47ehhipIhIaNYPKmSxACL0&callback=initMap&libraries=places">
</script>
<script>
    var markers = []; // массив маркеров
    var markerZEO;
    var imageZEO = '/static/img/logo_old.png';


    function deleteMarkers() {
        for (var i = 0; i < markers.length; i++) {
            markers[i].setMap(null);
        }
        markers = [];
    }

    // функция проложения маршрута
    function route(m1, m2, map) {
        var directionsDisplay = new google.maps.DirectionsRenderer();

        var request = {
            //origin: new google.maps.LatLng(60.023539414725356, 30.283663272857666), //точка старта
            origin: new google.maps.LatLng(m1.latLng), //точка старта
            //destination: new google.maps.LatLng(59.79530896374892, 30.410317182540894), //точка финиша
            destination: new google.maps.LatLng(m2.latLng), //точка финиша
            travelMode: google.maps.DirectionsTravelMode.DRIVING //режим прокладки маршрута
        };

        directionsService.route(request, function (response, status) {
            if (status == google.maps.DirectionsStatus.OK) {
                directionsDisplay.setDirections(response);
            }
        });

        directionsDisplay.setMap(map);
    }

    //------------------------------------- ИНИЦИАЛИЗАЦИЯ КАРТЫ -------------------------------------------------
    function initMap() {
        $("#address_button").on('click', function () {
            codeAddress(map)
        });//Кнопка поиска

        var address = document.getElementById('address').value;

        var myLatlng = new google.maps.LatLng(46.547940, 30.713923);
        var mapOptions = {
            zoom: 15,
            center: myLatlng
        };
        markerZEO = new google.maps.Marker({
            position: myLatlng,
            label: 'ЗС',
            icon: imageZEO
        });

        var map = new google.maps.Map(document.getElementById('map'), mapOptions);

        markerZEO.setMap(map);

        //Центрировать карту при инициализации если есть адресс в инпуте
        if (address) {
            var geocoder = new google.maps.Geocoder();
            geocoder.geocode({'address': address}, function (results, status) {
                if (status == 'OK') {
                    map.setCenter(results[0].geometry.location);
                    var marker = new google.maps.Marker({
                        map: map,
                        position: results[0].geometry.location
                    });
                    markers.push(marker);
                }
            });
        }

        google.maps.event.addListener(map, 'click', function (event) {
            addMarker(event.latLng, map);
        });


        //------------------------ AUTOCOMPLETE -----------------------
        var input = /** @type {!HTMLInputElement} */(
            document.getElementById('address'));

        var types = document.getElementById('type-selector');
//        map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);
        map.controls[google.maps.ControlPosition.TOP_LEFT].push(types);

        var autocomplete = new google.maps.places.Autocomplete(input);
        autocomplete.bindTo('bounds', map);

        var infowindow = new google.maps.InfoWindow();
        var marker = new google.maps.Marker({
            map: map,
            anchorPoint: new google.maps.Point(0, -29)
        });
        markers.push(marker);

        autocomplete.addListener('place_changed', function () {
            infowindow.close();
            marker.setVisible(false);
            var place = autocomplete.getPlace();
            if (!place.geometry) {
                // User entered the name of a Place that was not suggested and
                // pressed the Enter key, or the Place Details request failed.
                window.alert("Не найдено: '" + place.name + "'");
                return;
            }

            // If the place has a geometry, then present it on a map.
            if (place.geometry.viewport) {
                map.fitBounds(place.geometry.viewport);
            } else {
                map.setCenter(place.geometry.location);
                map.setZoom(17);  // Why 17? Because it looks good.
            }
            marker.setIcon(/** @type {google.maps.Icon} */({
                url: place.icon,
                size: new google.maps.Size(71, 71),
                origin: new google.maps.Point(0, 0),
                anchor: new google.maps.Point(17, 34),
                scaledSize: new google.maps.Size(35, 35)
            }));
            marker.setPosition(place.geometry.location);
            marker.setVisible(true);

            var address = '';
            if (place.address_components) {
                address = [
                    (place.address_components[0] && place.address_components[0].short_name || ''),
                    (place.address_components[1] && place.address_components[1].short_name || ''),
                    (place.address_components[2] && place.address_components[2].short_name || '')
                ].join(' ');
            }

            infowindow.setContent('<div><strong>' + place.name + '</strong><br>' + address);
            infowindow.open(map, marker);
        });
    }

    //------------------------ AUTOCMPLETE END ----------------------------
    //-------------------------------------- ИНИЦИАЛИЗАЦИЯ END ------------------------------------------------


    //------------------------- GEOCODING ---------------------------------------------------------------------
    // Формирование строк в input адресса и региона, results - то что возвращает гугл
    function setAdressAndRegion(results) {
        var region = '';
        var country = '';

        results[0].address_components.forEach(function (el, num, arr) {
//                    console.log(num + ' ' + el.types);
            if (el.types[0] === 'administrative_area_level_1') {// Level_1 - область
                region = el.long_name;
            }

            if (el.types[0] === 'country') {// Страна
                country = el.long_name;
            }
        });

        var str = region + ', ' + country;
        var replaceStr = str.replace(/ ?(область|обл|об) ?[.]?/gi, '');// Убирает слова типа "область" и если есть, пробелы вокруг него и точку в конце

        $('#region').val(replaceStr);
        $('#address').val(results[0].formatted_address);// Ответ от гугла - сформированный полный адресс
    }

    $('#address').on('blur', function () {
        $.blockUI();
        var geo = new google.maps.Geocoder();
        var geoAddress = $('#address').val();
        geo.geocode({'address': geoAddress}, function (results, status) {
            if (status == 'OK') {

                setAdressAndRegion(results);

                $.unblockUI();
//                console.log(results[0].formatted_address);
//                console.log(results[0].address_components);
                console.log(results[0]);
            } else {
                $.unblockUI();
                sendNotify('Неверно указан адрес...', 'info');
            }
        });
    });
    //----------------------------------- END ------------------------------------


    //------------- КНОПКА ПОИСКА ИЗ ПОЛЯ С АДРЕСОМ("#address")------------------
    function codeAddress(map) {
        $.blockUI();
        var myLatlng = new google.maps.LatLng(46.547940, 30.713923);
//        var mapOptions = {
//            zoom: 15,
//            center: myLatlng
//        };
//        var map = new google.maps.Map(document.getElementById('map'), mapOptions);
        var geocoder = new google.maps.Geocoder();
        var address = document.getElementById('address').value;
        console.log(address);
        geocoder.geocode({'address': address}, function (results, status) {
            if (status == 'OK') {
                deleteMarkers();
                map.setCenter(results[0].geometry.location);
                var marker = new google.maps.Marker({
                    map: map,
                    position: results[0].geometry.location
                });
                markers.push(marker);
            } else {
                sendNotify('Такого адреса не найдено: ' + status, 'error');
            }
        });
        $.unblockUI();
    }

    //------------------------------- END --------------------------------------

    //----------------- КЛИК ПО КАРТЕ -> ДОБАВЛЯЕТ МАРКЕР
    // И ЗАПОЛНЯЕТ ИНПУТ АДРЕСА ("#address")
    // И РЕГИОНА ("#region")  ------------------------------
    function addMarker(location, map) {
        var infowindow = new google.maps.InfoWindow;
        var geo = new google.maps.Geocoder();
        deleteMarkers();
        var clickMarker = new google.maps.Marker({
            position: location,
            map: map
        });
        markers.push(clickMarker);
        //route(markerZEO, clickMarker, map);
        var clickLatLng = clickMarker.position.toString();
        console.log(clickLatLng);

        var clickLatLngReplaced = clickLatLng.replace(/\(/i, '');
        console.log(clickLatLngReplaced);

        var latlngStr = clickLatLngReplaced.split(',', 2);
        console.log(latlngStr);

        var latlng = {lat: parseFloat(latlngStr[0]), lng: parseFloat(latlngStr[1])};
        console.log(latlng);

        geo.geocode({'location': latlng}, function (results, status) {
            if (status === 'OK') {
                if (results[1]) {
                    map.setZoom(17);
                    deleteMarkers();
                    var marker = new google.maps.Marker({
                        position: latlng,
                        map: map
                    });
                    markers.push(marker);
                    infowindow.setContent(results[1].formatted_address);
                    infowindow.open(map, marker);
                    $.blockUI();

                    setAdressAndRegion(results);

//                    console.log(results[0].formatted_address);
//                    console.log(results[0].address_components);
                    $.unblockUI();
                } else {
                    sendNotify('Такого адреса нет...', 'info');
                }
            } else {
                sendNotify('Ошибка геокодера: ' + status, 'error');
            }
        });
    }

    //    function geocodeLatLng(geocoder, map, infowindow) {
    //        var input = document.getElementById('latlng').value;
    //        var latlngStr = input.split(',', 2);
    //        var latlng = {lat: parseFloat(latlngStr[0]), lng: parseFloat(latlngStr[1])};
    //        geocoder.geocode({'location': latlng}, function(results, status) {
    //            if (status === 'OK') {
    //                if (results[1]) {
    //                    map.setZoom(11);
    //                    var marker = new google.maps.Marker({
    //                        position: latlng,
    //                        map: map
    //                    });
    //                    infowindow.setContent(results[1].formatted_address);
    //                    infowindow.open(map, marker);
    //                } else {
    //                    window.alert('No results found');
    //                }
    //            } else {
    //                window.alert('Geocoder failed due to: ' + status);
    //            }
    //        });
    //    }
    //--------------------------------------------------------------------------
    //--------------------------------------- GEOCODING END ----------------------------------------------------

</script>
<!----------------------GOOGLE MAP ENDS---------------------------->

<!-- Класс YANDEX карты -->
<script>
    //    class Map{
    //        constructor(region, address, distance, coord1, coord2) {
    //            this.formElements = [region, address, distance, coord1, coord2];
    //            this.formElementsFirstValues = [region.val(), address.val(), distance.val(), coord1.val(), coord2.val()];
    //
    //            this.firstSearch = true;
    //            this.searchAddr = false;
    //
    //            this.factoryLocation = [46.54812410352043,30.714186364417788];
    //            this.route = null;
    //
    //            //map
    //            this.map = new ymaps.Map('map', {
    //                center: [46.4838, 30.7357], // Одесса
    //                zoom: 12 //Приближение
    //            }, {
    //                searchControlProvider: 'yandex#search'
    //            });
    //            //dell type map
    //            this.map.controls.remove("searchControl").remove("typeSelector");
    //
    //            //add events handle
    //            <?php //if ($action && $action != "show") { ?>
    //            var tempThis = this;
    //            this.map.geoObjects.events.add('dragend', function (e) {
    //                var coords = e.get('target').geometry.getCoordinates();
    //                tempThis.showPlacemarkAndRoute(null, coords, tempThis);
    //            });
    //            this.map.events.add('click', function (e) {
    //                var coords = e.get('coords');
    //                tempThis.showPlacemarkAndRoute(null, coords, tempThis);
    //            });
    //            <?// } ?>
    //        }
    //
    //        searchAddress(address){
    //            this.searchAddr = true;
    //            try {
    //                this.showPlacemarkAndRoute(address, null, this);
    //            }
    //            catch (e){
    //                showErrors("Адрес не найден");
    //            }
    //        }
    //
    //        showByAddress(address){
    //            try {
    //                this.showPlacemarkAndRoute(address, null, this);
    //            }
    //            catch (e){
    //                showErrors("Адрес не найден");
    //            }
    //        }
    //
    //        showByCoordinates(latitude, longitude){
    //            try {
    //                this.showPlacemarkAndRoute(null, [latitude, longitude], this);
    //            }
    //            catch (e){
    //                showErrors("Таких координат не существует");
    //            }
    //        }
    //
    //        showPlacemarkAndRoute(inAddress, inCoords, tempThis) {
    //            var lastPoint = inAddress ? inAddress : inCoords;
    //            //route
    //            ymaps.route([
    //                tempThis.factoryLocation,
    //                lastPoint
    //            ],{
    //                mapStateAutoApply: true
    //            }).then(function (route) {
    //                tempThis.map.geoObjects.remove(tempThis.route);
    //                tempThis.map.geoObjects.add(tempThis.route = route);
    //
    //                var points = route.getWayPoints();
    //                var lastPoint = points.getLength() - 1;
    //
    //                ymaps.geocode(points.get(lastPoint).geometry.getCoordinates()).then(function (res) {
    //                    var globalAddress = res.geoObjects.get(0).properties.get('text'), localAddress, region;
    //
    //                    if (tempThis.firstSearch){
    //                        localAddress = tempThis.formElements[1].val();
    //                        region = tempThis.formElements[0].val();
    //                        tempThis.firstSearch = false;
    //                    }
    //                    else if (tempThis.searchAddr){
    //                        if (globalAddress != inAddress){
    //                            var answer = confirm("По данным коориднатам обнаружен другой адрес. Изменить адрес?");
    //                            if (answer){
    //                                localAddress = res.geoObjects.get(0).properties.get('name');
    //                                region = globalAddress.replace(localAddress, "").slice(0, -2);
    //                            }
    //                            else {
    //                                localAddress = tempThis.formElements[1].val();
    //                                region = tempThis.formElements[0].val();
    //                            }
    //                        }
    //                        else {
    //                            localAddress = tempThis.formElements[1].val();
    //                            region = tempThis.formElements[0].val();
    //                        }
    //                        tempThis.searchAddr = false;
    //                    }
    //                    else {
    //                        localAddress = res.geoObjects.get(0).properties.get('name');
    //                        region = globalAddress.replace(localAddress, "").slice(0, -2);
    //                    }
    //
    //                    //sett params
    //                    points.options.set('preset', 'twirl#redStretchyIcon');
    //                    points.get(0).properties.set('balloonContent', 'Завод');
    //                    points.get(0).properties.set('iconCaption', 'Завод');
    //                    points.get(0).options.set('hintContent', 'Завод');
    //                    points.get(0).options.set('preset', 'islands#greenDotIconWithCaption');
    //                    points.get(lastPoint).options.set('preset', 'islands#violetDotIconWithCaption');
    //                    points.get(lastPoint).properties.set('hintContent', localAddress);
    //                    points.get(lastPoint).options.set('hintContent', localAddress);
    //                    points.get(lastPoint).properties.set('balloonContent', localAddress);
    //                    <?// if ($action && $action != "show") { ?>
    //                        points.get(lastPoint).options.set( 'draggable', true);
    //                    <?// } ?>
    //
    //                    tempThis.changeFields(
    //                        [region,
    //                        localAddress,
    //                        Math.ceil(tempThis.route.getLength()/1000),
    //                        points.get(lastPoint).geometry.getCoordinates()[0],
    //                        points.get(lastPoint).geometry.getCoordinates()[1]],
    //                        tempThis
    //                    );
    //                });
    //            });
    //            //tempThis.map.panTo(coords);
    //        }
    //
    //        changeFields(valueArr, tempThis){
    //            for(var i = 0; i < tempThis.formElements.length; i++){
    //                if(tempThis.formElements[i].val() != valueArr[i]){
    //                    tempThis.formElements[i].addClass('green_background');
    //                    tempThis.formElements[i].removeClass('red_background');
    //                }
    //                if(tempThis.formElementsFirstValues[i] == valueArr[i]){
    //                    tempThis.formElements[i].removeClass('green_background');
    //                    tempThis.formElements[i].removeClass('red_background');
    //                }
    //                tempThis.formElements[i].val(valueArr[i]);
    //            }
    //        }
    //    }
</script>
<!-- Стили -->
<!-- Стили -->
<!-- Стили -->