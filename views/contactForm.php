<script type="text/JavaScript" src="/static/js/datepicker-ru.js"></script>
<?
// Рендер почты
function renderEmail($emails)
{
    if($emails) {
        foreach ($emails as $email) {
            $classId = rand(201, 400);
            echo "
        <tr>
            <td class='fancyTd'>
                <input type='text' id='email' name='email[]' style='width:86%;' class='fancyInput emailData' value='" . $email . "'>
                <img src='/static/img/minus_four.png' alt='Убрать email'  title='Убрать email' style='height: 25px; cursor: pointer;padding-right: 16px;' class='deleteNumber" . $classId . "' id='buttonPhoneNumberAdd' onclick='myConf(`Подтвердите действие`,`Вы уверены что хотите удалить эту почту?`,`del`," . $classId . ")'>
            </td>
        </tr>";
        }
    }
    echo "<tr id='emailTr' style='display: none'></tr>";
}

// Рендер номеров телефонов
function renderPhones($phones)
{
//    echo "<tr><td class='fancyTd label'><b>Добавить номер</b></td><td class='fancyTd'><img src='/static/img/plus3.png' alt='Добавить номер' title='Добавить номер' class='ttl' id='buttonPhoneNumberAdd' style='float: left;' onclick='addPhone()' onmouseover=\"this.src='/static/img/plus_new.png';\" onmouseout=\"this.src='/static/img/plus3.png';\"></td></tr>";
    if($phones) {
        foreach ($phones as $phone) {
            $classId = rand(1, 200);
            echo "
        <tr>
            <td class='fancyTd'>
                <input type='text' id='phone' name='phone[]' style='width:86%;' class='fancyInput phoneData' value='" . $phone . "'>
                <img src='/static/img/minus_four.png' alt='Убрать номер'  title='Убрать номер' style='height: 25px; cursor: pointer;padding-right: 16px;' class='deleteNumber" . $classId . "' id='buttonPhoneNumberAdd' onclick='myConf(`Подтвердите действие`,`Вы уверены что хотите удалить этот номер телефона?`,`del`," . $classId . ")'>
            </td>
        </tr>";
        }
    }
    echo "<tr id='phoneTr' style='display: none'></tr>";
}

?>
<?//=VarDumper::dump($action);

// action == create; action == edit
// Если редактирование $action == 'edit', $id == id контакта
if($action == 'edit'){//} || $action == 'show'){
    $contact = CustomerContacts::model()->findByPk($id);
    $fioValue = $contact->name;
    $position = $contact->position;
    $emails = json_decode($contact->email);
    $customerIdValue = $contact->id;
    $phones = json_decode($contact->phone);
    $notes = json_decode($contact->notes);
    if($contact->birthDate) {
        $birthDate = date('d.m.Y',strtotime($contact->birthDate));
    }else{
        $birthDate = '';
    }
}
// Если создание $action == 'create', $id == id заказчика
$action == 'create' || $action != 'edit' ? $visible = 'none' : $visible = '';
?>
<form>
    <table class="fancyTable">
        <!-- ФИО -->
        <tr>
            <td class="fancyTd">
                <b>ФИО</b>
            </td>
        </tr>
        <tr class="withBorder">
            <td class="fancyTd">
                <?if($action == 'edit'):?>
                    <input type="text" id="fio5" name="fio5" class="fancyInput" value="<?=$fioValue;?>">
                    <input type="hidden" id="customerId" name="customerId" value="<?=$customerIdValue;?>">
                    <input type="hidden" id="action" name="action" value="<?=$action;?>">
                <?else:?>
                    <input maxlength="64" type="text" id="fio5" name="fio5" class="fancyInput">
                    <input type="hidden" id="customerId" name="customerId" value="<?=$id;?>">
                    <input type="hidden" id="action" name="action" value="<?=$action;?>">
                <?endif;?>
            </td>
        </tr>

        <!-- Должность -->
        <tr>
            <td class="fancyTd label">
                <b>Должность</b>
            </td>
        </tr>
        <tr class="withBorder">
            <td class="fancyTd">
                <?if($action == 'edit'):?>
                    <input type="text" id="position" name="position" class="fancyInput" value="<?=$position;?>">
                <?else:?>
                    <input maxlength="64" type="text" id="position" name="position" class="fancyInput">
                <?endif;?>
            </td>
        </tr>

        <!-- Дата рождения -->
        <tr>
            <td class="fancyTd label">
                <b>Дата рождения</b>
            </td>
        </tr>
        <tr class="withBorder">
            <td class="fancyTd">
                <?if($action == 'edit'):?>
                    <input type="text" id="birthDate" name="birthDate" class="fancyInput" style="width: 20%; text-align: center" value="<?=$birthDate;?>" readonly>
                <?else:?>
                    <input type="text" id="birthDate" name="birthDate" class="fancyInput" style="width: 20%; text-align: center" readonly>
                <?endif;?>
            </td>
        </tr>

        <script>
// --------------- Коррекция даты выбора

            $('#birthDate').datepicker({
                changeMonth: true,
                changeYear: true,
                yearRange: '-70:-16',
                dateFormat : 'dd-mm-yy',
                defaultDate: new Date(2004, 0o0, 0o1)
            });

        </script>

        <!-- Телефон -->
        <tr>
            <td class="fancyTd label">
                <b>Телефон</b>
            </td>
        </tr>
        <?if($action == 'edit'):?>
            <?renderPhones($phones);?>
        <?else:?>
            <tr id="phoneTr">
                <td class="fancyTd">
                    <input maxlength="20" type="text" id="phone" name="phone[]" class="fancyInput phoneData">
                </td>
            </tr>
        <?endif;?>
        <tr class="withBorder">
            <td class="fancyTd" style="display: <?=$visible?>">
                <!--                <img src="/static/img/plus3.png" alt="Добавить номер" title="Добавить номер" class="ttl" id="buttonPhoneNumberAdd" onclick="addPhone()" onmouseover="this.src='/static/img/plus_new.png';" onmouseout="this.src='/static/img/plus3.png';">-->
                <span class="green_btn" onclick="addPhone()">Добавить номер</span>
            </td>
        </tr>

        <!-- Емейл -->
        <tr>
            <td class="fancyTd label">
                <b>Email</b>
            </td>
        </tr>
        <?if($action == 'edit'):?>
            <?renderEmail($emails);?>
        <?else:?>
            <tr id="emailTr">
                <td class="fancyTd">
                    <input maxlength="64" type="text" id="email" name="email[]" class="fancyInput">
                </td>
            </tr>
        <?endif;?>
        <tr class="withBorder">
            <td class="fancyTd" style="display: <?=$visible?>">

                <!--                <img src="/static/img/plus3.png" alt="Добавить номер" title="Добавить номер" class="ttl" id="buttonPhoneNumberAdd" onclick="addPhone()" onmouseover="this.src='/static/img/plus_new.png';" onmouseout="this.src='/static/img/plus3.png';">-->
                <span class="green_btn" onclick="addEmail()">Добавить email</span>
            </td>
        </tr>

        <!-- Примечания -->
        <tr>
            <td class="fancyTd label">
                <b>Примечания</b>
            </td>
        </tr>
        <tr class="withBorder">
            <td class="fancyTd">
                <?if($action == 'edit'):?>
                    <textarea name="notes[]" id="notes"></textarea>
                    <?if($notes):?>
                        <?$num = 1;?>
                        <?foreach($notes as $note):?>
                            <div style="width: 98%; background-color: #e7e7e7; border: 1px solid #b6b6b6; border-radius: 5px; padding: 5px; line-height: 1.5;" id="note<?=$num;?>">
                                <img src="/static/img/dell2.png" class="ttl" style="width: 15px; cursor: pointer; float: right" alt="Удалить" title="Удалить" onclick="myConf('Подтвердите действие','Вы уверены что хотите удалить это примечание?',`deleteNote`,<?=$customerIdValue;?>, <?=$num;?>)">
                                <?=$note;?>
                            </div>
                            <div class="clear" style="height: 10px"></div>
                            <?$num++;?>
                        <?endforeach;?>
                    <?endif;?>
                    <!--                    <input type="text" id="notes" name="notes" class="fancyInput" value="">-->
                <?else:?>
                    <textarea name="notes[]" id="notes"></textarea>
                    <!--                    <input type="text" id="notes" name="notes" class="fancyInput">-->
                <?endif;?>
            </td>
        </tr>
    </table>
</form>

<table class="fancyTable" style="height: 50px">
    <tr>
        <td class="buttons">
            <?if($id > 1) :?>
                 <input type="submit" class="green_btn" value="Сохранить" onclick="saveContact()">
            <?else:?>
                <input type="submit" class="green_btn" value="Сохранить" onclick="saveContactForm()">
            <?endif;?>
            <span class="green_btn" onclick="$.fancybox.close()">Закрыть</span>
        </td>
    </tr>
</table>

