<?php
?>
<style>


    .row {
        text-align: center;
    }

    @keyframes zoomIn {
        from {
            opacity: 0;
            transform: scale3d(0.3, 0.3, 0.3);
        }

        50% {
            opacity: 1;
        }
    }

    .box-popup, .box-popup-back, .box-popup-mini, .box-popup-back-mini {
        position: fixed;
        top: 0px;
        bottom: 0px;
        left: 0px;
        right: 0px;
        display: flex;
    }

    .box-popup, .box-popup-back {
        z-index: 980;
    }

    .box-popup-back, .box-popup-back-mini {

        transition-property: background, background-color;
        transition-duration: 0.5s;
        transition-timing-function: ease-in, ease, linear;
        background:rgba(0, 0, 0, 0.5)!important;
    }

    .box-popup-div, .box-popup-div-mini, .box-popup-div-center {
        margin: auto auto;
        /*width: 660px;*/
        min-width: 200px;
        height: auto;
        padding: 20px;
        position: relative;
    }

    .box-popup-div, .box-popup-div-mini{
        border-radius: 25px;
        background: #fff;
    }

    .box-popup-div{
        z-index: 981;
    }

    .box-popup-div h1, .box-popup-div-mini h1 {
        text-align: center;
        line-height: 24px;
        font-size: 22px;
    }

    .box-popup-div h2, .box-popup-div-mini h2 {
        font-size: 18px;
        line-height: 20px;
        margin: 0 0 16px 0;
        text-align: center;
    }

    .box-popup-div h3, .box-popup-div-mini h3 {
        font-size: 16px;
        line-height: 20px;
        font-weight: 600;
        margin: 0 0 20px 0;
    }

    .box-popup p {
        display: block;
        margin-block-start: 1em;
        margin-block-end: 1em;
        margin-inline-start: 0px;
        margin-inline-end: 0px;
    }

    .box-popup .preloader {
        z-index: 999;
    }

    .btn:not(:disabled):not(.disabled) {
        cursor: pointer;
    }
    .btn:not(:disabled):not(.disabled) {
        cursor: pointer;
    }

    .btn {
        display: inline-block;
        font-weight: 100;
        text-align: center;
        white-space: nowrap;
        vertical-align: middle;
        -webkit-user-select: none;
        -ms-user-select: none;
        user-select: none;
        border: 1px solid transparent;
        padding: .375rem .75rem;
        font-size: 1rem;
        line-height: 1.5;
        border-radius: .25rem;
        transition: color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out;
    }
    .w-50 {
        width: 30%!important;
    }
    .primary-button, .secondary-button {
        transition: .4s;
        color: #fff;
        text-decoration: none;
    }
    .primary-button.rollover {
        background-color: #3498db;
        font-size: 14px!important;
    }

    .primary-button.rollover:hover {
        background-color: #414b53;
    }
    .btn {
        display: inline-block;
        font-weight: 100;
        text-align: center;
        white-space: nowrap;
        vertical-align: middle;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
        border: 1px solid transparent;
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        line-height: 1.5;
        border-radius: 0.25rem;
        transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    div {
        display: block;
        margin: 0;
        padding: 0;
        line-height: 1;
    }
    body, div, dl, dt, dd, ul, ol, li, h1, h2, h3, h4, h5, h6, pre, code, form, fieldset, legend, input, textarea, p, blockquote, th, td {
        margin: 0;
        padding: 0;
    }
    *, :after, :before {
        box-sizing: inherit;
    }
    *, *::before, *::after {
        box-sizing: inherit;
    }
    * {
        padding: 0;
        margin: 0;
    }

    div {
        display: block;
    }
    .card .box-center {
        text-align: start;
    }

    #roots, input, select, textarea {
        font-family: Calibri,sans-serif;
        font-size: 14px;
    }

    body {
        font-size: 62.5%;
        margin: 0;
        background: #fff;
        font-weight: normal;
        height: 100%;
        font-family: Arial, Tahoma sans-serif;
        color: #000000;
        margin: 0 auto;
        min-width: 1200px;
    }
    :root {
        --duration: 1.5s;
        --container-size: 250px;
        --box-size: 33px;
        --box-border-radius: 15%;
    }
    html {
        -webkit-font-smoothing: antialiased;
    }
    *, :after, :before {
        box-sizing: inherit;
    }
    *, *::before, *::after {
        box-sizing: inherit;
    }
    *, :after, :before {
        box-sizing: inherit;
    }
    *, *::before, *::after {
        box-sizing: inherit;
    }

</style>

<div class="box-popup">
    <div id="modal-box" class="box-popup-back"></div>
    <div class="box-popup-div alert-enter-done">
        <div class="perform-activity" style="width: 380px;">
            <div class="row"><div class="col-12">
                    <h1 class="perform-activity__title">Вы уверины, что хотите перенести заказчика в архив?</h1>
                </div><br>
                <div class="col-12 mt-2">
                    <a type="submit" class="rollover btn primary-button w-50" id="archive" data-id="<?= $customer->id?>">Подтвердить</a>
                    <a type="reset" name="reset_kr" class="rollover btn primary-button w-50" onclick="$.fancybox.close()">Отменить</a>
                </div>
            </div>
        </div>
        <div class="cross-box">

        </div>
    </div>
</div>

<script>

    $('#archive').on('click', function () {
        var archiveId = $(this).data('id');
        $.ajax({
            url: '/customer/saveCustomerToArchive/id/' + archiveId,
            type: 'POST',
            dataType: 'json',
            success: function (data) {
                if (data.status === 'OK') {
                    $.unblockUI();
                    sendNotify('Заказчик перемещен в архив', 'success');

                    $('#in_archive<?= $customer->id ?>').remove();
                } else {
                    $.unblockUI();
                    sendNotify(data.error, 'error');
                }
            }
        });
        $.fancybox.close();
    });

</script>