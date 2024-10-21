// Настраиваем глобальные обработчики AJAX для блокировки кнопки
$(document).on('ajaxSetup', function (event, context) {
  context.options.beforeSend = function () {
    $("#patientForm").find("button[type='submit']").prop("disabled", true).text("Загрузка...");
  };
});

// Разблокируем кнопку после завершения запроса
$(document).on('ajaxComplete', function () {
  $("#patientForm").find("button[type='submit']").prop("disabled", false).text("Зарегистрировать");
});

// Обработка ответа формы
function handleFormResponse(data, formType) {
  if (data.error) {
    toastr.error(data.message);
  } else {
    toastr.success(data.message);

    // Сброс формы
    $("#patientForm")[0].reset();

    // Сброс селекта для врачей
    $("#doctor_id").val("").trigger("change");

    // Сброс даты и времени
    $("#appointment_date").val("").prop("disabled", true);
    $("#appointment_time").val("").prop("disabled", true);

    // Сброс чекбоксов
    $("#with_visit").prop("checked", false);
    $("#make_primary").prop("checked", false);

    // Скрытие элементов для типа визита
    $("#visit-details").addClass("d-none");
    $(".for-type-js").addClass("d-none");
    $("#make_primary_container").addClass("d-none");

    // Если был выбран врач и он остается постоянным, обновляем информацию о нем
    if (data.doctor && data.doctor.name && data.doctor.surname) {
      $("#doctor-info").html(
        "<b>Лечащий врач (Постоянный):</b> " +
        `<a href="/edit-doctor/${data.doctor.id}" class='float-right'>${data.doctor.name} ${data.doctor.surname}</a>` +
        '<button id="detach-doctor-button" class="btn btn-danger btn-xs float-right mt-2" onclick="detachDoctor(' +
        data.patient.id + ')">Открепить врача</button>'
      );
      $("#doctor_id").prop("disabled", true);
    } else {
      $("#doctor-info").html("<b>Лечащий врач (Постоянный):</b> <span class='float-right'>Не назначен</span>");
      $("#doctor_id").prop("disabled", false);
    }

    // Сброс селекта и чекбокса для назначения постоянного врача
    $("#doctor_id").val("");
    $("#doctor_id option:first").prop("selected", true);
    $("#make_primary").prop("checked", false);
    $("#make_primary_container").addClass("d-none");
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
          response.message + "</div>"
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
          response.message + "</div>"
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

// Скрываем чекбокс, если селект заблокирован
if ($("#doctor_id").is(":disabled")) {
  $("#make_primary_container").addClass("d-none");
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

// Валидация формы
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
      // iu_telephone: {
      //   required: true,
      //   phoneFormat: true,
      // },
      appointment_date: {
        required: true
      },
      appointment_time: {
        required: true
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
      // iu_telephone: {
      //   required: "Пожалуйста, введите номер телефона",
      //   phoneFormat: "Пожалуйста, введите номер телефона в формате 0(999) 999-999",
      // },
      appointment_date: {
        required: "Пожалуйста, введите дату визита",
      },
      appointment_time: {
        required: "Пожалуйста, введите время визита",
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
    language: "ru",
  });
}

if ($("#datepicker").length) {
  // Инициализация DatePicker
  $("#datepicker").datetimepicker({
    format: "DD.MM.YYYY",
    locale: "ru",
    useCurrent: false,
    minDate: moment().startOf("day"),
  });
}

if ($("#birthdate-datepicker").length) {
  // Инициализация DatePicker
  $("#birthdate-datepicker").datetimepicker({
    format: "DD.MM.YYYY",
    locale: "ru",
    useCurrent: false,
    maxDate: moment().startOf("day"),
  });
}

if ($("#timepicker").length) {
  // Инициализация TimePicker
  $("#timepicker").datetimepicker({
    format: "HH:mm",
    icons: {
      time: "fa fa-clock",
      up: "fa fa-arrow-up",
      down: "fa fa-arrow-down",
    },
    stepping: 1,
    useCurrent: false,
  });
}

if ($("#doctor_id").length) {
  var bookedTimes = [];
  $("#doctor_id").on("change", function () {
    var doctorId = $(this).val();
    if (doctorId) {
      $("#appointment_date").prop("disabled", false);
      $("#appointment_date").val(""); // Сбрасываем поле даты
      $("#appointment_time").val(""); // Сбрасываем поле времени
    } else {
      $("#appointment_date").prop("disabled", true);
      $("#appointment_time").prop("disabled", true);
    }
  });
}

var $picker = $("#datepicker");
if ($picker.length) {
  $picker.on("change.datetimepicker", function (e) {
    var doctorId = $("#doctor_id").val();
    var selectedDate = e.date ? e.date.format("DD.MM.YYYY") : null;
    if (!doctorId || !selectedDate) {
      $("#appointment_time").prop("disabled", true);
      return;
    }

    $("#appointment_time").prop("disabled", false);
    $("#detailed-schedule").removeClass("d-none");

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

          // Проверка на занятость выбранного времени
          $("#appointment_time")
            .off("change")
            .on("input", function () {
              var selectedTime = $("#appointment_time").val();
              if (data.times.includes(selectedTime)) {
                toastr.error("Это время уже забронировано. Пожалуйста, выберите другое время.");
                $("#appointment_time").val("");
              }
            });
        } else {
          // Если на выбранную дату нет забронированного времени
          $(document).Toasts("create", {
            class: "bg-info",
            title: "Информация",
            body: "Нет забронированного времени на выбранную дату " + selectedDate,
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

var calendarEl = document.getElementById("calendar");
var calendar;

if (calendarEl) {
  $("#modal-xl").on("shown.bs.modal", function (e) {
    var doctorId = $("#doctor_id").val();

    if (!doctorId) {
      $('#modal-warning').modal('show');
      $('#modal-xl').modal('hide');
      return;
    }

    if (!calendar) {
      calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: "dayGridMonth",
        locale: "ru",
        buttonText: {
          today: 'Сегодня'
        },
        buttonHints: {
          prev: 'Предыдущий месяц',
          next: 'Следующий месяц',
        },

        events: function (fetchInfo, successCallback, failureCallback) {
          $.request("onGetDoctorSchedule", {
            dataType: "json",
            data: {
              doctor_id: doctorId,
            },
            success: function (data) {
              if (data.error) {
                console.error("Ошибка с сервера:", data.message);
                alert("Ошибка с сервера: " + data.message);
                return;
              }

              var now = new Date();
              var events = data.times
                .filter(function (item) {
                  var eventDate = new Date(item.start);
                  return eventDate >= now;
                })
                .map(function (item) {
                  return {
                    title: item.title || "Запись",
                    start: item.start,
                    end: item.end,
                    allDay: false,
                  };
                });

              successCallback(events);
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
          meridiem: false,
          hour12: false,
        },
      });

      calendar.render();
    } else {
      calendar.refetchEvents();
    }
  });

  $("#modal-xl").on("hidden.bs.modal", function (e) {
    calendar.destroy();
    calendar = null;
  });
}

// Поиск пациентов
$("#patient_search").on("keyup", function () {
  var query = $(this).val();

  $.request("onSearchPatients", {
    data: { search_query: query },
    success: function (data) {
      // Обновляем содержимое таблицы вручную
      $("#patient_list").html(data['#patient_list']);
    },
    error: function () {
      console.error('Ошибка при поиске пациентов.');
    }
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

// Массив для хранения забронированных времен
var bookedTimes = [];

// Функция для получения забронированных времен
function getBookedTimes(doctorId, selectedDate) {
  $.request("onGetBookedTimes", {
    data: { doctor_id: doctorId, selected_date: selectedDate },
    success: function (data) {
      bookedTimes = data.times || []; // Обновляем массив забронированных времен
    },
    error: function () {
      alert("Ошибка при загрузке забронированных времен.");
    },
  });
}

// Обработчик события выбора даты
$("#datepicker").on("change.datetimepicker", function (e) {
  var doctorId = $("#doctor_id").val(); // Получаем ID врача
  var selectedDate = e.date.format("DD.MM.YYYY"); // Получаем выбранную дату

  if (!doctorId || !selectedDate) {
    $("#appointment_time").prop("disabled", true); // Блокируем выбор времени, если врач или дата не выбраны
    return;
  }

  $("#appointment_time").prop("disabled", false); // Разблокируем выбор времени
  getBookedTimes(doctorId, selectedDate); // Запрашиваем забронированные времена
});

// Проверка на занятость выбранного времени
$("#appointment_time").on("change", function () {
  var selectedTime = $(this).val() + ":00"; // Получаем выбранное время
  if (bookedTimes.includes(selectedTime)) {
    alert("Это время уже забронировано. Пожалуйста, выберите другое время.");
    $(this).val(""); // Сбрасываем выбранное время
  }
});

$("#visit_type").on("change", function () {
  var selectedType = $(this).val();

  if (selectedType === "амбулаторный") {
    $(".for-type-js").addClass("d-none"); // Скрываем элементы для амбулаторного визита

    // Сбрасываем селект с врачами на значение по умолчанию
    $("#doctor_id").val("").trigger("change");

    // Скрываем чекбокс и сбрасываем его
    $("#make_primary").prop("checked", false);
    $("#make_primary_container").addClass("d-none");

  } else if (selectedType === "стационарный") {
    $(".for-type-js").removeClass("d-none"); // Показываем элементы для стационарного визита
  } else {
    $(".for-type-js").addClass("d-none"); // Скрываем, если ничего не выбрано
  }
});


// Показываем/скрываем поля при выборе "с визитом"
$("#with_visit").on("change", function () {
  if ($(this).is(":checked")) {
    $("#visit-details").removeClass("d-none");
  } else {
    $("#visit-details").addClass("d-none");
    $("#visit_type").val("");
    $(".for-type-js").addClass("d-none");
  }
});

// Показываем поля для стационарного визита при выборе типа визита
$("#visit_type").on("change", function () {
  var selectedType = $(this).val();
  if (selectedType === "стационарный") {
    $(".for-type-js").removeClass("d-none");
  } else {
    $(".for-type-js").addClass("d-none");
  }
});




// Фильтрация записей
// $(function () {
//   $('#bookedfilter').daterangepicker({
//     locale: {
//       format: 'DD.MM.YYYY',
//       daysOfWeek: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
//       monthNames: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
//       applyLabel: 'Применить',
//       cancelLabel: 'Отмена',
//     }
//   });

//   $('#bookedfilter').on('apply.daterangepicker', function (ev, picker) {
//     $(this).val(picker.startDate.format('DD.MM.YYYY') + ' - ' + picker.endDate.format('DD.MM.YYYY'));
//   });

//   $('#bookedfilter').on('cancel.daterangepicker', function (ev, picker) {
//     $(this).val('');
//   });

//   // Обработчик клика по кнопке "Применить фильтр"
//   $(document).on('click', '.applyBtn', function () {
//     const selectedDates = $('#bookedfilter').val();
//     if (selectedDates) {
//       const dateRange = selectedDates.split(' - ');  // Разделяем диапазон дат
//       const startDate = dateRange[0];
//       const endDate = dateRange[1];

//       // Отправка выбранного диапазона дат на сервер для фильтрации
//       $.request('onFilterAppointmentsByDate', {
//         data: { start_date: startDate, end_date: endDate },  // Отправляем две переменные
//         success: function (response) {
//           $('#patient_list').html(response['#patient_list']);  // Обновляем таблицу
//         },
//         error: function () {
//           toastr.error('Произошла ошибка при фильтрации');  // Сообщение об ошибке через Toastr
//         }
//       });
//     }
//   });

//   // Обработчик клика по кнопке "Сбросить фильтр"
//   $('#reset-filter').on('click', function () {
//     $('#bookedfilter').val('');  // Сбрасываем значение поля
//     // Отправка запроса на сервер для получения всех пациентов без фильтрации
//     $.request('onFilterAppointmentsByDate', {
//       data: { start_date: '', end_date: '' },  // Передаем пустые даты
//       success: function (response) {
//         if (response.error) {
//           toastr.error(response.message || 'Ошибка при сбросе фильтра');
//         } else {
//           // Проверяем, что результат содержится в response и обновляем список
//           $('#patient_list').html(response['#patient_list']);  // Обновляем список пациентов
//           toastr.success('Фильтр сброшен');
//         }
//       },
//       error: function () {
//         toastr.error('Произошла ошибка при сбросе фильтра');
//       }
//     });
//   });

// });
