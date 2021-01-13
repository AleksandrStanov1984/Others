<?php
?>

<div style="font-size: 15px; text-align:left; font-family: Arial, Tahoma sans-serif; line-height: 1.5em; width: 300px;
 height: auto" xmlns="http://www.w3.org/1999/html">
    <div style="font-size: 16px; text-align: center; border-bottom: solid black 1px; line-height: 1.5em;
     font-family: Arial, Tahoma sans-serif;">Древо компании <br/>
    </div>

    <div><br/>Главная:
        <? if (!($customer->isChild()) && (!($customer->isParent()))) { ?>
            <span style=" color: black " id="new-edit">
                <?= $customer->name ?><br/><br/>
            </span>
            <? ?>
        <? } else if (!($customer->isChild())) { ?>
            <span style=" color: black "><?= $customer->isParent() ? $customer->name : $customer->parent->name ?>
                <br/><br/>
            </span>
            <? foreach ($customer->childes as $child) { ?>
                <? if (Yii::app()->user->regionId <= 0) { ?>
                    <div style="font-size: 15px; text-align:left; font-family: Arial, Tahoma sans-serif; line-height: 1.5em;">
                        Дочерняя:
                        <a href="/customer/edit/id/<?= $child->id ?>" id="new-edit"
                           style=" color: #006faf;"><?= $child->name; ?><br/></a>
                    </div>
                <? } else { ?>
                    <? if (in_array($child->regionId, $userRegions)) { ?>
                        <div style="font-size: 15px; text-align:left; font-family: Arial, Tahoma sans-serif; line-height: 1.5em;">
                            Дочерняя:
                            <a href="/customer/edit/id/<?= $child->id ?>" id="new-edit"
                               style=" color: #006faf;"><?= $child->name; ?><br/></a>
                        </div>
                    <? } else { ?>
                        <div style="font-size: 15px; text-align:left; font-family: Arial, Tahoma sans-serif; line-height: 1.5em;">
                            Дочерняя:
                            <span title="У Вас нет прав доступа к региону заказчика!"
                                  class="ttl"><?= $child->name; ?><br/>
                        </span>
                        </div>
                    <? } ?>
                <? } ?>
            <? } ?>
        <? } else { ?>
            <? if (Yii::app()->user->regionId <= 0) { ?>
                <a href="/customer/edit/id/<?= $customer->isParent() ? $customer->id : $customer->parent->id ?> "
                   id="new-edit" style=" color: #006faf; ">
                    <?= $customer->isParent() ? $customer->name : $customer->parent->name ?><br/><br/>
                </a>
            <? } else { ?>
                <? if (in_array($customer->isParent() ? $customer->regionId : $customer->parent->regionId, $userRegions)) { ?>
                    <a href="/customer/edit/id/<?= $customer->isParent() ? $customer->id : $customer->parent->id ?> "
                       id="new-edit" style=" color: #006faf; ">
                        <?= $customer->isParent() ? $customer->name : $customer->parent->name ?><br/><br/>
                    </a>
                <? } else { ?>
                    <span title="У Вас нет прав доступа к региону заказчика!" class="ttl">
                    <?= $customer->isParent() ? $customer->name : $customer->parent->name ?><br/><br/>
                </span>
                <? } ?>
            <? } ?>
            <? foreach ($customer->parent->childes as $child) { ?>
                <? if (Yii::app()->user->regionId <= 0) { ?>
                    <div style="font-size: 15px; text-align:left; font-family: Arial, Tahoma sans-serif; line-height: 1.5em;">
                        Дочерняя:
                        <? if ($customer->id == $child->id) { ?>
                            <span style=" color: black " id="new-edit">
                            <?= $child->name ?>
                            </span>
                        <? } else { ?>
                            <a href="/customer/edit/id/<?= $child->id ?>" id="new-edit" style=" color: #006faf;">
                                <?= $child->name; ?><br/>
                            </a>
                        <? } ?>
                    </div>
                <? } else { ?>
                    <? if (in_array($child->regionId, $userRegions)) { ?>
                        <div style="font-size: 15px; text-align:left; font-family: Arial, Tahoma sans-serif; line-height: 1.5em;">
                            Дочерняя:
                            <? if ($customer->id == $child->id) { ?>
                                <span style=" color: black " id="new-edit">
                            <?= $child->name ?>
                            </span>
                            <? } else { ?>
                                <a href="/customer/edit/id/<?= $child->id ?>" id="new-edit" style=" color: #006faf;">
                                    <?= $child->name; ?><br/>
                                </a>
                            <? } ?>
                        </div>
                    <? } else { ?>
                        <div style="font-size: 15px; text-align:left; font-family: Arial, Tahoma sans-serif; line-height: 1.5em;">
                            Дочерняя:
                            <? if ($customer->id == $child->id) { ?>
                                <span style=" color: black " id="new-edit">
                            <?= $child->name ?>
                        </span>
                            <? } else { ?>
                                <span title="У Вас нет прав доступа к региону заказчика!" class="ttl">
                                    <?= $child->name; ?><br/>
                            </span>
                            <? } ?>
                        </div>
                    <? } ?>
                <? } ?>
            <? } ?>
        <? } ?>
    </div>
</div>