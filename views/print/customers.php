
<?php

header("Content-type: application/xls");
header('Content-Disposition: attachment; filename=customers'.date('d.m.Y').'.xls');
header('Pragma: no-cache');
header('Expires: 0');

$regionRow = $regionId == 'all' ? true : false;

?>
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
</head>
<body>
<!--<h4>Список заказчиков на --><?//=date('d.m.Y');?><!-- (--><?//= $regionName; ?><!--)</h4>-->
<table border="1" cellpadding="0" cellspacing="0">
    <thead>
    <tr>
        <th>№</th>
        <th>Наименование</th>
        <th>Юридический адрес</th>
        <th>ЕГРПОУ</th>
        <? if ($regionRow) : ?>
            <th>Регион</th>
        <? endif; ?>
    </tr>
    </thead>
    <tbody>
    <?$num = 1;?>
    <? foreach ($customers as $customer) : ?>
        <tr>
            <td><?= $num; ?></td>
            <td><?= $customer->name; ?></td>
            <td><?= $customer->lawAdres ?: 'нет юр.адреса'; ?></td>
            <td><?= $customer->egrpou ?: 'нет кода'; ?></td>
            <? if ($regionRow) : ?>
                <td><?= $customer->innerRegion->name; ?></td>
            <? endif; ?>
            <? foreach ($customer->contacts as $contact) : ?>
                <? if (!empty($contact->name) && !$contact->isDell) : ?>
                    <?
                    $phones = json_decode($contact->phone);
                    $emails = json_decode($contact->email);
                    ?>
                    <td>
                        <?= $contact->name; ?>
                    </td>
                    <td><?= !empty($contact->position) ? $contact->position : 'должность не указана' ; ?></td>
                    <td>
                        <? if ($phones) : ?>
                            <dl>
                                <? foreach ($phones as $phone) : ?>
                                    <dd><?= $phone; ?></dd>
                                <? endforeach; ?>
                            </dl>
                        <? else : ?>
                            нет телефона
                        <? endif; ?>
                    </td>
                    <td>
                        <? if ($emails) : ?>
                            <dl>
                                <? foreach ($emails as $email) : ?>
                                    <dd><?= $email; ?></dd>
                                <? endforeach; ?>
                            </dl>
                        <? else : ?>
                            нет почты
                        <? endif; ?>
                    </td>
                <? endif;?>
            <? endforeach; ?>
        </tr>
        <?$num++;?>
    <? endforeach; ?>
    </tbody>
</table>
</body>
</html>
