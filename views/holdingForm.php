<form id="propData">
    <!-- Скрытые поля -->
    <div class="hiddenInputs">
        <?if($action == 'edit'):?>
            <input type="hidden" name="holdingId" value="<?=$holding->id;?>">
        <?endif;?>
        <input type="hidden" name="action" value="<?=$action;?>">
    </div>
    <table class="fancyTable">
        <!-- НАИМЕНОВАНИЕ -->
        <tr>
            <td class="fancyTd">
                <b>Наименование</b>
            </td>
        </tr>
        <tr class="withBorder">
            <td class="fancyTd">
                <?if($action == 'create'):?>
                    <input type="text" name="name" class="fancyInput" value="">
                <?else:?>
                    <input type="text" <?= $action == 'view'? 'readonly disabled' : ' '?> name="name" class="fancyInput" value="<?=$holding->name;?>">
                <?endif;?>
            </td>
        </tr>

        <?if($action != 'create'):?>
        <!-- КОМПАНИИ -->
        <tr>
            <td class="fancyTd">
                <b>Компании</b>
            </td>
        </tr>
        <tr class="withBorder">
            <td class="fancyTd">
                <?if($holding->customers):?>
                    <table>
                        <?foreach($holding->customers as $c):?>
                            <tr>
                                <td style="height: 30px;">
                                    <a href="/customer/show/id/<?=$c->id?>" target="_blank"><?=$c->name?></a>
                                </td>
                            </tr>
                        <?endforeach;?>
                    </table>
                <?else:?>
                    <p>Нет компаний входящих в состав холдинга</p>
                <?endif;?>
            </td>
        </tr>
        <?endif;?>
    </table>
</form>

<table class="fancyTable" style="height: 50px">
    <tr>
        <td class="buttons">
            <?if($action != 'view'):?>
                <input type="submit" class="green_btn" value="Сохранить" onclick="saveHolding()">
            <?endif;?>
            <span class="green_btn" onclick="$.fancybox.close()">Закрыть</span>
        </td>
    </tr>
</table>