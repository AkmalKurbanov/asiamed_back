function handleFormResponse(data, formType) {
    if (data.error) {
        // Выводим сообщение об ошибке
        $("#flash-message").html(
            '<div class="alert alert-danger" role="alert">' +
                data.message +
                "</div>"
        );
    } else {
        // Выводим сообщение об успехе
        $("#flash-message").html(
            '<div class="alert alert-success" role="alert">' +
                data.message +
                "</div>"
        );

        // Обновляем информацию о враче
        if (data.doctor && data.doctor.name && data.doctor.surname) {
            // Обновляем информацию о враче, если врач есть
            $("#doctor-info").html(
                "<p>Лечащий врач (Постоянный): " +
                    data.doctor.name +
                    " " +
                    data.doctor.surname +
                    "</p>" +
                    '<button id="detach-doctor-button" class="btn btn-danger" onclick="detachDoctor(' +
                    data.patient.id +
                    ')">Открепить врача</button>'
            );

            // Блокируем селект врача, так как врач назначен
            $("#doctor_id").prop("disabled", true);
        } else {
            // Если врача нет (doctor_id null)
            $("#doctor-info").html(
                "<p>Лечащий врач (Постоянный): Не назначен</p>"
            );

            // Разблокируем селект врача, так как врач откреплен
            $("#doctor_id").prop("disabled", false);
        }

        // Сбрасываем селект до дефолтного значения
        $("#doctor_id").val(""); // Очищаем текущее значение
        $("#doctor_id option:first").prop("selected", true); // Устанавливаем дефолтный option

        // Скрываем чекбокс, так как действие завершено
        $("#make_primary_container").hide();
        $("#make_primary").prop("checked", false);
    }

    // Убираем сообщение через 5 секунд
    setTimeout(function () {
        $("#flash-message").fadeOut(500, function () {
            $(this).html("").show();
        });
    }, 5000);
}

// Функция открепления врача
function detachDoctor(patientId) {
    $.request("onDetachDoctor", {
        data: { patient_id: patientId },
        success: function (response) {
            if (response.error) {
                $("#flash-message").html(
                    '<div class="alert alert-danger" role="alert">' +
                        response.message +
                        "</div>"
                );
            } else {
                // Обновляем интерфейс: меняем текст на "Врач не назначен"
                $("#doctor-info").html(
                    "<p>Лечащий врач (Постоянный): Не назначен</p>"
                );

                // Разблокируем селект для выбора врача
                $("#doctor_id").prop("disabled", false);

                // Сбрасываем выбранное значение селекта на дефолтное
                $("#doctor_id").val("");

                // Скрываем чекбокс
                $("#make_primary_container").hide();

                // Убираем кнопку открепления
                $("#detach-doctor-button").remove();

                // Показать сообщение об успехе
                $("#flash-message").html(
                    '<div class="alert alert-success" role="alert">' +
                        response.message +
                        "</div>"
                );

                // Убираем сообщение через 5 секунд
                setTimeout(function () {
                    $("#flash-message").fadeOut(500, function () {
                        $(this).html("").show();
                    });
                }, 5000);
            }
        },
        error: function () {
            $("#flash-message").html(
                '<div class="alert alert-danger" role="alert">Ошибка при выполнении запроса.</div>'
            );
        },
    });
}

$(document).ready(function () {
    // Скрываем чекбокс, если врач уже добавлен и селект заблокирован
    if ($("#doctor_id").is(":disabled")) {
        $("#make_primary_container").hide();
    }

    // Показываем чекбокс при выборе врача в селекте
    $("#doctor_id").on("change", function () {
        if ($(this).val()) {
            $("#make_primary_container").show();
        } else {
            $("#make_primary_container").hide();
        }
    });
});

$(document).ready(function () {
    // Инициализация jQuery UI Datepicker
    $("#appointment_date, #visit_date").datepicker({
        dateFormat: "dd.mm.yy",
        changeMonth: false,
        changeYear: false,
        minDate: 0,
        regional: "ru",
        showButtonPanel: true,
        closeText: "Закрыть",
        currentText: "Сегодня",
        showAnim: "slideDown",
    });

    // Инициализация ClockPicker
    $("#appointment_time").clockpicker({
        autoclose: true,
        default: "now",
        placement: "bottom",
        align: "left",
        donetext: "Готово",
    });

    // Сброс даты и времени при выборе врача
    $("#doctor_id").on("change", function () {
        $("#appointment_date").val("");
        $("#appointment_time").val("");
        $("#booked_times")
            .empty()
            .append('<li class="list-group-item">Выберите дату</li>');
    });

    // Сброс времени при изменении даты
    $("#appointment_date").on("change", function () {
        $("#appointment_time").val("");
    });

    // Запрос на получение забронированного времени при изменении врача или даты
    $("#doctor_id, #appointment_date").on("change", function () {
        var doctorId = $("#doctor_id").val();
        var selectedDate = $("#appointment_date").val();

        if (doctorId && selectedDate) {
            $.request("onGetBookedTimes", {
                data: { doctor_id: doctorId, selected_date: selectedDate },
                success: function (data) {
                    $("#booked_times").empty();
                    if (data.times && data.times.length > 0) {
                        data.times.forEach(function (item) {
                            var formattedTime = item.time.slice(0, 5);
                            $("#booked_times").append(
                                '<li class="list-group-item">' +
                                    item.day_of_week +
                                    ", " +
                                    item.date +
                                    " - " +
                                    formattedTime +
                                    "</li>"
                            );
                        });

                        var bookedTimes = data.times.map(function (item) {
                            return item.time;
                        });

                        $("#appointment_time")
                            .off("change")
                            .on("change", function () {
                                var selectedTime =
                                    $("#appointment_time").val() + ":00";
                                if (bookedTimes.includes(selectedTime)) {
                                    alert(
                                        "Это время уже забронировано. Пожалуйста, выберите другое время."
                                    );
                                    $("#appointment_time").val("");
                                }
                            });
                    } else {
                        $("#booked_times").append(
                            '<li class="list-group-item">Нет забронированного времени</li>'
                        );
                        $("#appointment_time").off("change");
                    }
                },
            });
        } else {
            $("#booked_times")
                .empty()
                .append(
                    '<li class="list-group-item">Выберите врача и дату</li>'
                );
            $("#appointment_time").off("change");
        }
    });

    // Переключение вида таблиц
    $(".tile-visible-js").on("click", function () {
        $(".table-list").hide();
        $(".tile-list").show();
        $(window).scrollTop($(window).scrollTop() + 1);
    });

    $(".table-visible-js").on("click", function () {
        $(".tile-list").hide();
        $(".table-list").show();
    });

    // Поиск пациентов
    $("#patient_search").on("keyup", function () {
        var query = $(this).val();
        $.request("onSearchPatients", {
            data: { search_query: query },
            update: { patient_list: "#patient_list" },
        });
    });

    // Поиск врачей
    $("#doctor_search").on("keyup", function () {
        var query = $(this).val();
        $.request("onSearchDoctors", {
            data: { search_query: query },
            update: { doctor_list: "#doctor_list" },
        });
    });

    // Datepicker для других дат
    $("#start_date, #end_date, #birthdate").datepicker({
        dateFormat: "dd.mm.yy",
        regional: "ru",
        changeMonth: true,
        changeYear: true,
        maxDate: 0,
        yearRange: "-100:+0",
        showAnim: "slideDown",
    });

    // Переключение вкладок
    $("#patientTabs a").on("click", function (e) {
        e.preventDefault();
        $(this).tab("show");
    });

    $("#patientTabs a:first").tab("show");

    // Показ чекбокса для назначения врача
    $("#doctor_id").on("change", function () {
        var selectedDoctor = this.value;
        if (selectedDoctor) {
            $("#make_primary_container").show();
        } else {
            $("#make_primary_container").hide();
        }
    });
});

function detachDoctor(patientId) {
    $.request("onDetachDoctor", {
        data: { patient_id: patientId },
        success: function (response) {
            handleFormResponse(response, "detachDoctor");
        },
        error: function () {
            $("#flash-message").html(
                '<div class="alert alert-danger" role="alert">Ошибка при выполнении запроса.</div>'
            );
        },
    });
}
