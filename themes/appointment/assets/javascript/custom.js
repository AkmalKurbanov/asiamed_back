
function handleFormResponse(data, formType) {
    // Проверка на ошибки
    if (data.error) {
        // Выводим сообщение об ошибке
        $("#flash-message").html(
            '<div class="alert alert-danger">' + data.message + "</div>"
        );
    } else {
        // Выводим сообщение об успехе
        $("#flash-message").html(
            '<div class="alert alert-success">' + data.message + "</div>"
        );

        // Сбрасываем форму только для создания врача или пациента
        if (formType === "createDoctor") {
            $("#doctorForm")[0].reset();
        } else if (formType === "createPatient") {
            $("#patientForm")[0].reset();
        } else if (formType === "editPatient") {
            // Сообщение для редактирования пациента
            console.log("Пациент успешно обновлен");
        }
    }

    // Убираем сообщение через 5 секунд
    setTimeout(function () {
        $("#flash-message").html("");
    }, 5000);
}

$(document).ready(function () {
    // Инициализация jQuery UI Datepicker с кнопками
    $("#appointment_date").datepicker({
        dateFormat: "dd.mm.yy", // Формат даты: день.месяц.год
        changeMonth: false, // Отключаем выбор месяца
        changeYear: false, // Отключаем выбор года
        minDate: 0, // Блокируем прошедшие даты (минимум сегодня)
        regional: "ru", // Устанавливаем русский язык
        showButtonPanel: true, // Включаем отображение кнопок
        closeText: "Закрыть", // Текст для кнопки закрытия
        currentText: "Сегодня", // Текст для кнопки "Сегодня"
        showAnim: "slideDown", // Анимация при открытии
    });

    // Обработчик для кнопки "Сегодня"
    $(document).on("click", ".ui-datepicker-current", function () {
        $("#appointment_date").datepicker("setDate", new Date()); // Устанавливаем текущую дату
    });
});

$(document).ready(function () {
    // Инициализация ClockPicker
    var clockpicker = $("#appointment_time").clockpicker({
        autoclose: true, // Закрыть после выбора времени
        default: "now", // Установить текущее время как значение по умолчанию
        placement: "bottom", // Открывать ниже поля ввода
        align: "left", // Выравнивание
        donetext: "Готово", // Текст кнопки завершения
        
    });
});

// При выборе врача сбрасываем поля даты и времени
$("#doctor_id").on("change", function () {
    // Сбрасываем поле даты
    $("#appointment_date").val("");
    // Сбрасываем поле времени
    $("#appointment_time").val("");

    // Очищаем список забронированного времени
    $("#booked_times")
        .empty()
        .append('<li class="list-group-item">Выберите дату</li>');
});

// При изменении даты сбрасываем поле времени
$("#appointment_date").on("change", function () {
    $("#appointment_time").val(""); // Сбрасываем поле времени
});

// При изменении врача или даты
$("#doctor_id, #appointment_date").on("change", function () {
    var doctorId = $("#doctor_id").val();
    var selectedDate = $("#appointment_date").val();

    // Проверяем, что выбраны врач и дата
    if (doctorId && selectedDate) {
        console.log(
            "Запрашиваем забронированное время для врача с ID:",
            doctorId,
            "и даты:",
            selectedDate
        );

        // Выполняем AJAX-запрос для получения забронированного времени
        $.request("onGetBookedTimes", {
            data: { doctor_id: doctorId, selected_date: selectedDate },
            success: function (data) {
                console.log("Данные от сервера:", data);

                // Очищаем список забронированного времени
                $("#booked_times").empty();

                if (data.times && data.times.length > 0) {
                    // Добавляем забронированное время в список с днем недели и датой
                    data.times.forEach(function (item) {
                        var formattedTime = item.time.slice(0, 5); // Время без секунд (HH:mm)
                        var hiddenTime = item.time; // Полное время для сравнения (HH:mm:ss)

                        // Добавляем элемент списка с видимым временем и скрытым элементом
                        $("#booked_times").append(
                            '<li class="list-group-item">' +
                                item.day_of_week +
                                ", " +
                                item.date +
                                " - " +
                                formattedTime +
                                '<input type="hidden" class="booked-time-hidden" value="' +
                                hiddenTime +
                                '">' +
                                "</li>"
                        );
                    });

                    // Заблокируем уже занятое время в Clockpicker
                    var bookedTimes = data.times.map(function (item) {
                        return item.time; // Массив забронированного времени с секундами
                    });

                    console.log(
                        "Забронированное время (с секундами):",
                        bookedTimes
                    );

                    // Добавляем обработчик для изменения времени
                    $("#appointment_time")
                        .off("change")
                        .on("change", function () {
                            var selectedTime = $("#appointment_time").val();
                            var formattedTime = selectedTime + ":00"; // Добавляем секунды для сравнения

                            console.log(
                                "Выбранное время (с секундами):",
                                formattedTime
                            );

                            // Проверка совпадения выбранного времени с забронированным
                            if (bookedTimes.includes(formattedTime)) {
                                alert(
                                    "Это время уже забронировано. Пожалуйста, выберите другое время."
                                );
                                $("#appointment_time").val(""); // Очищаем поле, если время занято
                            }
                        });
                } else {
                    // Если нет забронированного времени, очищаем обработчик на appointment_time
                    $("#booked_times").append(
                        '<li class="list-group-item">Нет забронированного времени</li>'
                    );
                    $("#appointment_time").off("change"); // Убираем обработчик, если нет забронированных времен
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error("Ошибка при запросе:", textStatus, errorThrown);
            },
        });
    } else {
        console.log("Врач или дата не выбраны.");
        $("#booked_times")
            .empty()
            .append('<li class="list-group-item">Выберите врача и дату</li>');
        $("#appointment_time").off("change"); // Убираем обработчик, если врач или дата не выбраны
    }
});



$(".tile-visible-js").on('click', function(){
    $(".table-list").hide();
    $(".tile-list").show();
    $(window).scrollTop($(window).scrollTop() + 1);
});

$(".table-visible-js").on('click', function(){
    $(".tile-list").hide();
    $(".table-list").show();
});








// скрипт для динамического поиска
$(document).ready(function () {
    // Обработчик ввода в поле поиска
    $("#patient_search").on("keyup", function () {
        var query = $(this).val(); // Получаем введенный текст

        // Выполняем Ajax-запрос на поиск пациентов
        $.request("onSearchPatients", {
            data: { search_query: query }, // Отправляем введенный запрос
            update: { patient_list: "#patient_list" }, // Обновляем partial 'patient_list'
        });
    });
});
$(document).ready(function () {
    // Обработчик ввода в поле поиска
    $("#doctor_search").on("keyup", function () {
        var query = $(this).val(); // Получаем введенный текст

        // Выполняем Ajax-запрос на поиск пациентов
        $.request("onSearchDoctors", {
            data: { search_query: query }, // Отправляем введенный запрос
            update: { patient_list: "#doctor_list" }, // Обновляем partial 'patient_list'
        });
    });
});

