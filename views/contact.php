<style>
    .fancyContent {
        margin: 10px;
        border: 1px solid #b6b6b6;
    }

    .fancyTable {
        width: 600px;
        height: 300px;
        margin: 15px;
    }

    .label {
        /*width: 150px;*/
        height: 30px;
        padding-top: 10px;
    }

    .fancyTd {
        height: 40px;
        /*border-bottom: solid 1px #b6b6b6;*/
        font-size: medium;
        padding-bottom: 10px;
    }

    .buttons {
        height: 50px;
        padding-top: 10px;
        float: right;
        /*padding-top: 10px;*/
    }

    .fancyInput {
        border-radius: 5px;
        height: 20px;
        width: 95%;
        padding: 5px 8px;
    }

    .withBorder {
        border-bottom: solid 1px #b6b6b6;
        padding-bottom: 10px;
    }

    /*#phone {*/
    /*width: 260px;*/
    /*}*/
    #buttonPhoneNumberAdd {
        width: 25px;
        height: 25px;
        padding-right: 30px;
        padding-top: 4px;
        float: right;
        cursor: pointer;
    }

    textarea {
        width: 100%;
        height: 75px;
        resize: none; /* Запрещаем изменять размер */
        border-radius: 5px;
    }
</style>


<? //if($action == 'edit' || $action == 'show'):?>
<div class="myConf"></div>
<div class="fancyContent">
    <?= $this->renderPartial('components/contactForm', array('action' => $action, 'id' => $id), true); ?>
</div>
<? //endif; ?>
<script>

    $(document).ready(function () {
//        $('.phoneData').mask("+99 (999) 999-9999");
    });

    function addEmail() {
        var classId = Math.floor(Math.random() * (500 - 201 + 201)) + 201;
        var input = $('#emailTr').after("<tr><td class=fancyTd><input type='text' id='email' name='email[]' style=' width:86%;' class='fancyInput emailData'><img src='/static/img/minus_four.png' alt='Убрать email'  title='Убрать email' style='height: 25px;padding-right: 16px;' class='deleteNumber" + classId + "' id='buttonPhoneNumberAdd' onclick='myConf(`Подтвердите действие`,`Вы уверены что хотите удалить эту почту?`,`del`," + classId + ")'></td></tr>");
    }

    function addPhone() {
        var classId = Math.floor(Math.random() * (200 - 1 + 1)) + 1;
        var input = $('#phoneTr').after("<tr><td class=fancyTd><input type='text' id='phone' name='phone[]' style=' width:86%;' class='fancyInput phoneData'><img src='/static/img/minus_four.png' alt='Убрать номер'  title='Убрать номер' style='height: 25px; padding-right: 16px;' class='deleteNumber" + classId + "' id='buttonPhoneNumberAdd' onclick='myConf(`Подтвердите действие`,`Вы уверены что хотите удалить этот номер телефона?`,`del`," + classId + ")'></td></tr>");
//        appendMask(input);
    }

    function appendMask() {
        $('.phoneData').each(function () {
            $(this).mask("+99 (999) 999-9999");
        });
    }


    function del(classId) {
        $('.__holder').hide();
        $('.deleteNumber' + classId).closest('tr').remove();
    }

    function saveContactForm() {
        let error = false;
        if ($('#fio5').val().length <= 0) {
            sendNotify('Не указано ФИО.', 'error');
            return false;
        }
        if ($('#position').val().length <= 0) {
            sendNotify('Не указана Должность.', 'error');
            return false;
        }
        if ($('#phone').val().length <= 0) {
            sendNotify('Не указан Телефон.', 'error');
            return false;
        }

        let contact = {};
        contact.name = $('#fio5').val();
        contact.position = $('#position').val();
        contact.birthDate = $('#birthDate').val();
        contact.phone = $('#phone').val();
        contact.email = $('#email').val();
        contact.notes = $('#notes').val();

        contacts.push(contact);

        console.log(contacts);
        let table = $('#tableContact');

        let tr = "<tr class='line_in_form_table contactsTr' style='font-size: 12px; line-height: 1.5'>";
        tr = tr + "<td style='font-size: 14px'>" + "<b>" + contact.name + "</b>" + "</td>";
        tr = tr + "<td>" + contact.position + "</b>" + "</td>";
        tr = tr + "<td>" + "</td>";
        tr = tr + "<td>" + contact.phone + "</td>";
        tr = tr + "<td>" + contact.email + "</td>";
        tr = tr + "<td>" + contact.notes + "</td>";
        tr = tr + "<td class='action_buttons'>" + "<div>" +
            "<img src='/static/img/edit4.png' style='width: 25px; cursor: pointer; opacity: 0.50' class='ttl contact_buttons' alt='Недоступно' title='Недоступно'>" +
            "<img src='/static/img/delete_red.png' style='width: 25px; cursor: pointer; padding-left: 20px; opacity: 0.50;' class='ttl contact_buttons' alt='Недоступно' title='Недоступно'>" +
            "</td>";
        tr = tr + "</tr> + <div> + </div>";

        $(table).append(tr);
        sendNotify('Операция успешно завершена.', 'success');
        $.fancybox.close();
    }


    function saveContact() {
        $.blockUI();
        var error = false;
        if ($('#customerId').val().length <= 0) {
            error = true;
            $.unblockUI();
            sendNotify('Утерян ID.', 'error');
        }
        if ($('#fio5').val().length <= 0) {
            error = true;
            $.unblockUI();
            sendNotify('Не указано ФИО.', 'error');
        }
        if ($('#position').val().length <= 0) {
            error = true;
            $.unblockUI();
            sendNotify('Не указана Должность.', 'error');
        }
        if ($('#phone').val().length <= 0) {
            error = true;
            $.unblockUI();
            sendNotify('Не указан номер Телефона', 'error');
        }

        if (!error) {
            $.post('<?php echo $this->createUrl('SaveContact')?>',
                $('form').serialize()
                , function (data) {
                    data = $.parseJSON(data);
                    if (data.success) {
                        $('.contact_table > tbody:last').append('<tr class="child"><td class="fio"><span>' + $('#fio').val() + '</span></td><td class="email"><span>' + $('#email').val() + '</span></td><td class="phone">' + $('#phone').val() + '</td><td class="action_buttons"><div><img src="/static/img/edit5.png" style="width: 25px; float: left; cursor: pointer" alt="Редактировать" title="Редактирвать" class="ttl" onclick="editContact(' + $('#customerId').val() + ')"><img src="/static/img/delete_red.png" style="width: 25px; cursor: pointer; padding-left: 20px" alt="Удалить" title="Удалить" class="ttl" onclick="deleteContact(' + $('#customerId').val() + ')"></div></td></tr>');

                        $('.fancyContent').empty();
                        $('.fancyContent').fadeOut(100, function () {
                            $('.fancyContent').html(data.conf).fadeIn(100);
                        });
                        $.unblockUI();
                        sendNotify('Операция успешно завершена.', 'success');
                    } else {
                        $.unblockUI();
                        sendNotify(data.error, 'error');
                    }
                }
            );
        }
        $.fancybox.close();
    }

    function deleteNote(id, num) {
        $('.__holder').hide();
        var text = $('#note' + num).text();

        $.blockUI();

        $.post('<?php echo $this->createUrl('DeleteNote')?>',
            {
                id: id,
                text: text
            }
            , function (data) {
                data = $.parseJSON(data);
                if (data.success) {
                    $.unblockUI();
                    sendNotify('Операция успешно завершена.', 'success');
                    $('#note' + num).remove();
                } else {
                    $.unblockUI();
                    sendNotify(data.error, 'error');
                }
            }
        );
    }
</script>