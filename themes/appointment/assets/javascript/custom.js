$(document).ready(function () {
  // Настраиваем глобальные обработчики AJAX для блокировки кнопки
  $(document).on('ajaxSetup', function (event, context) {
    // Перед отправкой запроса
    context.options.beforeSend = function () {
      // Блокируем кнопку отправки
      $("#patientForm").find("button[type='submit']").prop("disabled", true).text("Загрузка...");
    };
  });

  // Разблокируем кнопку после завершения запроса
  $(document).on('ajaxComplete', function () {
    // Разблокируем кнопку
    $("#patientForm").find("button[type='submit']").prop("disabled", false).text("Зарегистрировать");
  });
});

function handleFormResponse(data, formType) {
    if (data.error) {
        // Выводим сообщение об ошибке
        toastr.error(data.message);

    } else {
        // Выводим сообщение об успехе
        toastr.success(data.message);
     
        $("#patientForm")[0].reset();
        $("#doctor_id").val("").trigger("change");
        
      
        // Обновляем информацию о враче
        if (data.doctor && data.doctor.name && data.doctor.surname) {
            // Обновляем информацию о враче, если врач есть
            $("#doctor-info").html(
                "<b>Лечащий врач (Постоянный):</b> " +
                    `<a href="/edit-doctor/${data.doctor.id}" class='float-right'>${data.doctor.name} ${data.doctor.surname}</a>`
                    +
                    '<button id="detach-doctor-button" class="btn btn-danger btn-xs float-right mt-2" onclick="detachDoctor(' +
                    data.patient.id +
                    ')">Открепить врача</button>'
            );

            // Блокируем селект врача, так как врач назначен
            $("#doctor_id").prop("disabled", true);
        } else {
            // Если врача нет (doctor_id null)
            $("#doctor-info").html(
                "<b>Лечащий врач (Постоянный):</b> <span class='float-right'>Не назначен</span> "
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
                    "<b>Лечащий врач (Постоянный):</b> <span class='float-right'>Не назначен</span>"
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
    // Скрываем чекбокс, если селект заблокирован
    if ($("#doctor_id").is(":disabled")) {
        $("#make_primary_container").hide();
    }

    // Показываем чекбокс при выборе врача в селекте
    $("#doctor_id").on("change", function () {
        var selectedValue = $(this).val();
        if (selectedValue && selectedValue !== "Выберите врача") {
            $("#make_primary_container").removeClass("d-none"); // Показываем чекбокс
        } else {
            $("#make_primary_container").addClass("d-none"); // Скрываем чекбокс
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
//   // валидация форм
  $(function () {
    // Добавляем новое правило для проверки на кириллицу
    $.validator.addMethod(
        "cyrillic",
        function (value, element) {
            return this.optional(element) || /^[а-яА-ЯёЁ\s]+$/.test(value);
        },
        "Пожалуйста, используйте только кириллические символы"
    );

    // Добавляем правило для проверки формата телефона
    $.validator.addMethod(
        "phoneFormat",
        function (value, element) {
            return this.optional(element) || /^0\(\d{3}\) \d{3}-\d{3}$/.test(value);
        },
        "Пожалуйста, введите номер телефона в формате 0(999) 999-999"
    );

    // Валидация формы
    $(".validate-form").validate({
        rules: {
            name: {
                required: true,
                cyrillic: true,
            },
            surname: {
                required: true,
                cyrillic: true,
            },
            email: {
                required: true,
                email: true,
            },
            password: {
                required: true,
                minlength: 5,
            },
            iu_telephone: {
                required: true,
                phoneFormat: true,
            },
            terms: {
                required: true,
            },
        },
        messages: {
            name: {
                required: "Пожалуйста, введите имя",
                cyrillic: "Пожалуйста, используйте только кириллические символы",
            },
            surname: {
                required: "Пожалуйста, введите фамилию",
                cyrillic: "Пожалуйста, используйте только кириллические символы",
            },
            email: {
                required: "Пожалуйста, введите адрес электронной почты",
                email: "Пожалуйста, введите действительный адрес электронной почты.",
            },
            password: {
                required: "Пожалуйста, укажите пароль",
                minlength: "Пароль должен быть длиной не менее 8 символов",
            },
            iu_telephone: {
                required: "Пожалуйста, введите номер телефона",
                phoneFormat: "Пожалуйста, введите номер телефона в формате 0(999) 999-999",
            },
            terms: "Пожалуйста, примите наши условия",
        },
        errorElement: "span",
        errorPlacement: function (error, element) {
            error.addClass("invalid-feedback");
            element.closest(".form-group").append(error);
        },
        highlight: function (element, errorClass, validClass) {
            $(element).addClass("is-invalid");
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).removeClass("is-invalid");
        },
    });
});

    

    // Проверка наличия элементов перед их использованием
    if ($("[data-mask]").length) {
      $("[data-mask]").inputmask();
    }

    if ($(".select2").length) {
      $(".select2").select2({
        language: "ru", // Указание языка
      });
    }
    
    if ($("#datepicker").length) {
        // Инициализация DatePicker
        $("#datepicker").datetimepicker({
          format: "DD.MM.YYYY", // Устанавливаем формат даты (день.месяц.год)
          locale: "ru", // Локализация на русский язык
            useCurrent: false, // Отключить автоматический выбор текущей даты
            minDate: moment().startOf("day"), // Запрещаем выбор прошедших дат
        });
    }

    if ($("#birthdate-datepicker").length) {
        // Инициализация DatePicker
        $("#birthdate-datepicker").datetimepicker({
          format: "DD.MM.YYYY", // Устанавливаем формат даты (день.месяц.год)
          locale: "ru", // Локализация на русский язык
            useCurrent: false, // Отключить автоматический выбор текущей даты
            maxDate: moment().startOf("day"), // Запрещаем выбор будущих дат
        });
    }
    
    if ($("#timepicker").length) {
        // Инициализация TimePicker
        $("#timepicker").datetimepicker({
          format: "HH:mm", // Формат времени (часы:минуты)
          icons: {
            time: "fa fa-clock",
            up: "fa fa-arrow-up",
            down: "fa fa-arrow-down",
          },
          stepping: 1, // Шаг времени в минутах
          useCurrent: false, // Чтобы время не устанавливалось автоматически
        });
      }
      
      if ($("#doctor_id").length) {
        // Разблокировать поле даты при выборе врача
        $("#doctor_id").on("change", function () {
          var doctorId = $(this).val();
            if (doctorId) {
                $("#appointment_date").prop("disabled", false); // Разблокировать поле даты
              } else {
                $("#appointment_date").prop("disabled", true); // Если врач не выбран, блокируем дату и время
                $("#appointment_time").prop("disabled", true);
              }
            });
          }
          
          var $picker = $("#datepicker");
          
          if ($picker.length) {
            // Обработчик события change.datetimepicker
        $picker.on("change.datetimepicker", function (e) {
          var doctorId = $("#doctor_id").val(); // Получаем ID врача
            var selectedDate = e.date ? e.date.format("DD.MM.YYYY") : null; // Получаем выбранную дату
            
            // Проверяем, выбраны ли дата и врач
            if (!doctorId || !selectedDate) {
              $("#appointment_time").prop("disabled", true); // Блокируем поле выбора времени, если не выбраны врач или дата
              return;
            }
            
            $("#appointment_time").prop("disabled", false); // Разблокируем поле времени, если выбраны и врач, и дата
            $("#detailed-schedule").removeClass("d-none"); // Разблокируем поле времени, если выбраны и врач, и дата

            // Отправляем запрос на сервер для получения забронированных времен
            $.request("onGetBookedTimes", {
                data: { doctor_id: doctorId, selected_date: selectedDate },
                success: function (data) {
                  $("#booked_times").empty(); // Очищаем предыдущие записи о забронированном времени
                  
                  if (data.times && data.times.length > 0) {
                    data.times.forEach(function (item) {
                      // Показываем уведомление с забронированным временем и датой
                      $(document).Toasts("create", {
                        class: "bg-warning",
                                title: "Забронировано на " + selectedDate,
                                body: "Время: " + item,
                              });
                        });
                        
                        var bookedTimes = data.times.map(function (item) {
                          return item;
                        });

                        // Проверка на занятость выбранного времени
                        $("#appointment_time")
                        .off("change")
                        .on("change", function () {
                                var selectedTime =
                                    $("#appointment_time").val() + ":00";
                                    if (bookedTimes.includes(selectedTime)) {
                                      alert(
                                        "Это время уже забронировано. Пожалуйста, выберите другое время."
                                    );
                                    $("#appointment_time").val(""); // Сбрасываем время, если оно занято
                                }
                            });
                          } else {
                            // Если на выбранную дату нет забронированного времени
                        $(document).Toasts("create", {
                            class: "bg-info",
                            title: "Информация",
                            body:
                                "Нет забронированного времени на выбранную дату " +
                                selectedDate,
                        });
                        
                        $("#appointment_time").off("change"); // Сбрасываем обработчик событий
                    }
                },
                error: function () {
                  alert("Ошибка при загрузке забронированных времен.");
                },
            });
        });
    }




$(document).ready(function () {
  var calendarEl = document.getElementById("calendar");
  var calendar;

  if (calendarEl) {
    // Когда модальное окно открывается
    $("#modal-xl").on("shown.bs.modal", function (e) {
      var doctorId = $("#doctor_id").val(); // ID выбранного врача

      // Проверяем, выбран ли врач
      if (!doctorId) {
        $('#modal-warning').modal('show');
        $('#modal-xl').modal('hide');
        return;
      }

      if (!calendar) {
        // Инициализация календаря
        calendar = new FullCalendar.Calendar(calendarEl, {
          initialView: "dayGridMonth", // Отображение по месяцам
          locale: "ru", // Локализация календаря на русский язык
          buttonText: {
            today: 'Сегодня'
          },
          buttonHints: {
            prev: 'Предыдущий месяц',
            next: 'Следующий месяц',
          },

          events: function (fetchInfo, successCallback, failureCallback) {
            // Выполняем AJAX запрос к методу onGetDoctorSchedule для получения всех событий
            $.request("onGetDoctorSchedule", {
              dataType: "json",
              data: {
                doctor_id: doctorId, // Передаем только ID врача
              },
              success: function (data) {
                if (data.error) {
                  console.error("Ошибка с сервера:", data.message);
                  alert("Ошибка с сервера: " + data.message);
                  return;
                }

                // Фильтрация событий, чтобы показывать только будущие события
                var now = new Date(); // Текущая дата и время
                var events = data.times
                  .filter(function (item) {
                    var eventDate = new Date(item.start);
                    return eventDate >= now; // Только будущие события
                  })
                  .map(function (item) {
                    return {
                      title: item.title || "Запись", // Заголовок события
                      start: item.start, // Дата начала события
                      end: item.end, // Дата окончания события
                      allDay: false, // Устанавливаем как событие, привязанное ко времени
                    };
                  });

                successCallback(events); // Передаем события в календарь
              },
              error: function () {
                alert("Ошибка при загрузке расписания врача");
                failureCallback();
              },
            });
          },
          eventTimeFormat: {
            hour: "2-digit",
            minute: "2-digit",
            meridiem: false, // Убираем AM/PM формат, если используется
            hour12: false, // 24-часовой формат
          },
        });

        // Рендерим календарь
        calendar.render();
      } else {
        // Если календарь уже был инициализирован, перезагружаем события
        calendar.refetchEvents();
      }
    });

    // При закрытии модального окна сбрасываем календарь, если нужно
    $("#modal-xl").on("hidden.bs.modal", function (e) {
      calendar.destroy(); // Разрушаем календарь, чтобы не было дублирования
      calendar = null; // Обнуляем переменную
    });
  }
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





