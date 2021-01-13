<?php ?>
<script src="//api-maps.yandex.ru/2.1/?lang=ru_RU" type="text/javascript"></script>
<style>
    .td_form_customer{
        padding: 5px;
        border-bottom: solid 1px #b6b6b6;
    }
    .left_td_form_customer{
        border-right: solid 1px #b6b6b6;
    }
    .input_form_customer{
        border-radius: 5px;
        height:20px;
        width:280px;
        padding:5px 8px;
    }
    .replacefield{
        background: #a2ffab;
    }

    .no_value{
        background: #ff3f44;
    }
</style>

<div style="float: left;">
    <table id="table_info">
        <tr>
            <td class="td_form_customer left_td_form_customer">
                <div id="">Название</div>
            </td>
            <td class="td_form_customer">
                <input type="text" id="name" class="input_form_customer" value=''>
            </td>
        </tr>
        <tr>
            <td class="td_form_customer left_td_form_customer">
                <div id="">Контактное лицо</div>
            </td>
            <td class="td_form_customer">
                <input type="text" id="fio" class="input_form_customer" value="">
            </td>
        </tr>
        <tr>
            <td class="td_form_customer left_td_form_customer">
                <div id="">Местоположение</div>
            </td>
            <td class="td_form_customer">
                <input type="text" id="region" class="input_form_customer" value="">
            </td>
        </tr>
        <tr>
            <td class="td_form_customer left_td_form_customer">
                <div id="">Адрес</div>
            </td>
            <td class="td_form_customer " colspan="2">
                <input type="text" id="address" class="input_form_customer"  style="width: 240px; float: left;" value="">
                <img width="25px" height="25px" id="address_button" disabled="disabled" class="button_input_addr" style="margin: 5px 8px;" src="/static/img/search-blue.png" title="Показать">
            </td>
        </tr>
        <tr>
            <td class="td_form_customer left_td_form_customer">
                <div id="">Расстояние, км</div>
            </td>
            <td class="td_form_customer">
                <input type="text" style="text-align: center" id="distance" class="input_form_customer" readonly value="">
            </td>
        </tr>
        <tr>
            <td class="td_form_customer left_td_form_customer">
                <div id="">Широта</div>
            </td>
            <td class="td_form_customer">
                <input type="text" style="text-align: center" id="coord1" class="input_form_customer" readonly data="" value="">
            </td>
        </tr>
        <tr>
            <td class="td_form_customer left_td_form_customer">
                <div id="">Долгота</div>
            </td>
            <td class="td_form_customer">
                <input type="text" style="text-align: center" id="coord2" class="input_form_customer" readonly data="" value="">
            </td>
        </tr>
        <tr>
            <td colspan="2" class="td_form_customer" style="text-align: center">
                <input type="submit" id="save_customer" class="green_btn_green" style="float: none" disabled="disabled" value="Добавить">
            </td>
        </tr>
    </table>
</div>

<div id="map" style="margin-left: 50px; width: 60%; height: 600px; float: left"></div>

<script>
    class Map{
        constructor() {
            this.coordsXField = $('#coord1');
            this.coordsYField = $('#coord2');
            this.lengthField = $('#distance');
            this.addressField = $('#address');
            this.regionField = $('#region');

            this.firstSearch = true;
            this.searchAddr = false;

            this.factoryLocation = [46.54812410352043,30.714186364417788];
            this.route = null;

            this.map = new ymaps.Map('map', {
                center: [46.4838, 30.7357], // Одесса
                zoom: 12 //Приближение
            }, {
                searchControlProvider: 'yandex#search'
            });
            this.map.controls.remove("searchControl").remove("typeSelector");

            var tempThis = this;
            this.map.geoObjects.events.add('dragend', function (e) {
                var coords = e.get('target').geometry.getCoordinates();
                tempThis.showPlacemarkAndRoute(null, coords, tempThis);
            });
            this.map.events.add('click', function (e) {
                var coords = e.get('coords');
                tempThis.showPlacemarkAndRoute(null, coords, tempThis);
            });
        }

        searchAddress(address){
            this.searchAddr = true;
            try {
                this.showPlacemarkAndRoute(address, null, this);
            }
            catch (e){
                this.showErrors("Адрес не найден");
            }
        }

        showByAddress(address){
            try {
                this.showPlacemarkAndRoute(address, null, this);
            }
            catch (e){
                this.showErrors("Адрес не найден");
            }
        }

        showByCoordinates(latitude, longitude){
            try {
                this.showPlacemarkAndRoute(null, [latitude, longitude], this);
            }
            catch (e){
                this.showErrors("Таких координат не существует");
            }
        }

        showPlacemarkAndRoute(inAddress, inCoords, tempThis) {
            var lastPoint = inAddress ? inAddress : inCoords;
            //route
            ymaps.route([
                tempThis.factoryLocation,
                lastPoint
            ],{mapStateAutoApply: true}).then(function (route) {
                tempThis.map.geoObjects.remove(tempThis.route);
                tempThis.map.geoObjects.add(tempThis.route = route);

                var points = route.getWayPoints(),
                    lastPoint = points.getLength() - 1;

                ymaps.geocode(points.get(lastPoint).geometry.getCoordinates()).then(function (res) {
                    var globalAddress = res.geoObjects.get(0).properties.get('text'), localAddress, region;

                    if (tempThis.firstSearch){
                        localAddress = tempThis.addressField.val();
                        region = tempThis.regionField.val();
                        tempThis.firstSearch = false;
                    }
                    else if (tempThis.searchAddr){
                        if (globalAddress != inAddress){
                            var answer = confirm("По данным коориднатам обнаружен другой адрес. Изменить адрес?");
                            if (answer){
                                localAddress = res.geoObjects.get(0).properties.get('name');
                                region = globalAddress.replace(localAddress, "").slice(0, -2);
                            }
                            else {
                                localAddress = tempThis.addressField.val();
                                region = tempThis.regionField.val();
                            }
                        }
                        else {
                            localAddress = tempThis.addressField.val();
                            region = tempThis.regionField.val();
                        }
                        tempThis.searchAddr = false;
                    }
                    else {
                        localAddress = res.geoObjects.get(0).properties.get('name');
                        region = globalAddress.replace(localAddress, "").slice(0, -2);
                    }

                    //sett params
                    points.options.set('preset', 'twirl#redStretchyIcon');
                    points.get(0).properties.set('balloonContent', 'Завод');
                    points.get(0).properties.set('iconCaption', 'Завод');
                    points.get(0).options.set('hintContent', 'Завод');
                    points.get(0).options.set('preset', 'islands#greenDotIconWithCaption');
                    points.get(lastPoint).options.set('preset', 'islands#violetDotIconWithCaption');
                    points.get(lastPoint).options.set( 'draggable', true);
                    points.get(lastPoint).properties.set('hintContent', localAddress);
                    points.get(lastPoint).options.set('hintContent', localAddress);
                    points.get(lastPoint).properties.set('balloonContent', localAddress);

                    tempThis.changeFields(
                        region,
                        localAddress,
                        Math.ceil(tempThis.route.getLength()/1000),
                        points.get(lastPoint).geometry.getCoordinates()[0],
                        points.get(lastPoint).geometry.getCoordinates()[1],
                        tempThis
                    );
                });
            });
            //tempThis.map.panTo(coords);
        }

        changeFields(region, address, lengthPath, coordX, coordY, tempThis){
            if(tempThis.coordsXField.val() != coordX){
                tempThis.coordsXField.val(coordX);
                tempThis.coordsXField.addClass('replacefield');
                tempThis.coordsXField.removeClass('no_value');
            }
            if(tempThis.coordsYField.val() != coordY){
                tempThis.coordsYField.val(coordY);
                tempThis.coordsYField.addClass('replacefield');
                tempThis.coordsYField.removeClass('no_value');
            }
            if(tempThis.lengthField.val() != lengthPath){
                tempThis.lengthField.val(lengthPath);
                tempThis.lengthField.addClass('replacefield');
                tempThis.lengthField.removeClass('no_value');
            }
            if(tempThis.addressField.val() != address){
                tempThis.addressField.val(address);
                tempThis.addressField.addClass('replacefield');
                tempThis.addressField.removeClass('no_value');
            }
            if(tempThis.regionField.val() != region){
                tempThis.regionField.val(region);
                tempThis.regionField.addClass('replacefield');
                tempThis.regionField.removeClass('no_value');
            }
        }

        showErrors(msg){
            var elem = "" +
                "<tr id='error' class='error'>" +
                "<td class='td_form_customer' colspan='2' style='text-align: center; background-color: #ff3f44; height: 30px;'>"
                + msg +
                "</td>" +
                "</tr>";
            $('#table_info').find("tr.error").hide();
            $('#table_info').find("tr.info").hide();
            $('#table_info').append(elem);
        }

        showInfo(msg){
            var elem = "" +
                "<tr id='info' class='info'>" +
                "<td class='td_form_customer' colspan='2' style='text-align: center; background-color: #5fff54; height: 30px;'>"
                + msg +
                "</td>" +
                "</tr>";
            $('#table_info').find("tr.error").hide();
            $('#table_info').find("tr.info").hide();
            $('#table_info').append(elem);
        }
    }
    var map;
</script>

<script>
    $('#region').keyup(function(event){
        if(event.keyCode==13)
        {
            $('#address').focus();
        }
    });
    $('#address').keyup(function(event){
        if(event.keyCode==13)
        {
            $('.button_input_addr').click();
        }
    });
    $('.button_input_addr').click(function () {
        map.searchAddress($('#region').val() + ", " + $('#address').val());
    });
</script>

<script>
    $('#table_info').change(function(event){
        $('#'+event.target.id).removeClass('no_value');;
    });

    $('#save_customer').click(function () {
        var ok = true;
        $('#table_info').find('.input_form_customer').removeClass('no_value');
        if ($('#name').val() == ""){
            $('#name').addClass('no_value');
            ok = false;
        }
        if ($('#fio').val() == ""){
            $('#fio').addClass('no_value');
            ok = false;
        }
        if ($('#region').val() == ""){
            $('#region').addClass('no_value');
            ok = false;
        }
        if ($('#address').val() == ""){
            $('#address').addClass('no_value');
            ok = false;
        }
        if ($('#distance').val() == ""){
            $('#distance').addClass('no_value');
            ok = false;
        }
        if ($('#coord1').val() == ""){
            $('#coord1').addClass('no_value');
            ok = false;
        }
        if ($('#coord2').val() == ""){
            $('#coord2').addClass('no_value');
            ok = false;
        }

        if(!ok) {
            map.showErrors("Есть незаполененые поля!");
        }
        else {
            $.post('<?php echo $this->createUrl('Save')?>',
                {
                    name: $('#name').val(),
                    fio: $('#fio').val(),
                    region: $('#region').val(),
                    address: $('#address').val(),
                    distance: $('#distance').val(),
                    coord1: $('#coord1').val(),
                    coord2: $('#coord2').val()
                }, function (data) {
                    data = $.parseJSON(data);
                    if(data['status'] == "OK"){
                        $('#table_info').find('.input_form_customer').removeClass('replacefield');
                        map.showInfo("Заказчик добавлен!!!");
                    }
                    else {
                        map.showErrors(data['error']);
                    }
                });
        }
    });
</script>

<script type="text/javascript">
    $(function () {
        ymaps.ready(function () {
            map = new Map();
            map.firstSearch = false;
            $('#address_button').prop( "disabled", false );
            $('#save_customer').prop( "disabled", false );
        });
    })
</script>