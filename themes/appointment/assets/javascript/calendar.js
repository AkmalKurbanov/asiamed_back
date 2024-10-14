$(function () {
  /* Инициализация внешних событий */
  function ini_events(ele) {
    ele.each(function () {
      var eventObject = {
        title: $.trim($(this).text()),
        backgroundColor: $(this).css('background-color'),
        borderColor: $(this).css('border-color')
      };

      $(this).data('eventObject', eventObject);

      // Делаем событие перетаскиваемым
      $(this).draggable({
        zIndex: 1070,
        revert: true,
        revertDuration: 0
      });
    });
  }

  ini_events($('#external-events div.external-event'));

  var currColor = '#3c8dbc';

  var Calendar = FullCalendar.Calendar;
  var Draggable = FullCalendar.Draggable;

  var containerEl = document.getElementById('external-events');
  var calendarEl = document.getElementById('calendar');

  /* Инициализация Draggable для внешних событий */
  new Draggable(containerEl, {
    itemSelector: '.external-event',
    eventData: function (eventEl) {
      return {
        title: eventEl.innerText,
        backgroundColor: window.getComputedStyle(eventEl, null).getPropertyValue('background-color'),
        borderColor: window.getComputedStyle(eventEl, null).getPropertyValue('background-color'),
        textColor: window.getComputedStyle(eventEl, null).getPropertyValue('color'),
        event_id: eventEl.getAttribute('data-event-id') // Используем атрибут event_id
      };
    }
  });

  // Инициализация календаря
  var calendar = new FullCalendar.Calendar(calendarEl, {
    locale: "ru",
    themeSystem: 'bootstrap',
    timeZone: 'local', // Установка локальной временной зоны
    editable: true,
    droppable: true,
    eventDisplay: 'block',
    nextDayThreshold: '23:59',

    // Настройка формата отображения времени для событий
    eventTimeFormat: {
      hour: '2-digit',
      minute: '2-digit',
      hour12: false // Используем 24-часовой формат
    },

    // Загрузка событий с сервера
    events: function (fetchInfo, successCallback, failureCallback) {
      $.request('eventManagement::onLoadEvents', {
        success: function (data) {
          if (data.error) {
            failureCallback();
            toastr.error('Не удалось загрузить события');
          } else {
            var events = data.events.map(function (event) {
              // Время уже будет конвертироваться в локальную зону благодаря timeZone: 'local'
              console.log('Загруженное событие:');
              console.log('ID события:', event.id);
              console.log('Start time (UTC -> Asia/Bishkek):', event.start);
              console.log('End time (UTC -> Asia/Bishkek):', event.end);

              return event;  // Отправляем события без преобразования, так как FullCalendar работает в локальной зоне
            });
            successCallback(events);
          }
        },
        error: function () {
          failureCallback();
          toastr.error('Произошла ошибка при загрузке событий');
        }
      });
    },

    // Обработка перетаскивания события
    eventDrop: function (info) {
      // Преобразуем новое время в UTC для отправки на сервер
      var newStartTime = moment(info.event.start).utc().toISOString();
      var newEndTime = info.event.end ? moment(info.event.end).utc().toISOString() : null;

      // Логируем перед отправкой на сервер
      console.log("Отправляемые данные при перетаскивании:");
      console.log("Start time (UTC):", newStartTime);
      console.log("End time (UTC):", newEndTime);

      // Отправляем данные на сервер
      var eventData = {
        event_id: info.event.extendedProps.event_id,
        start_time: newStartTime,
        end_time: newEndTime // Передаем время окончания или null, если его нет
      };

      $.request('eventManagement::onUpdateEvent', {
        data: eventData,
        success: function (response) {
          if (!response.error) {
            toastr.success('Событие успешно обновлено.');
          } else {
            toastr.error('Ошибка при обновлении события.');
          }
        },
        error: function () {
          toastr.error('Произошла ошибка при обновлении события.');
        }
      });
    },

    // Обработка изменения размера события
    eventResize: function (info) {
      var eventId = info.event.extendedProps ? info.event.extendedProps.event_id : null;
      if (!eventId) {
        toastr.error('ID события отсутствует, обновление невозможно.');
        return;
      }

      var eventData = {
        event_id: eventId,
        start_time: info.event.start.toISOString(),
        end_time: null // Устанавливаем null для однодневных событий
      };

      // Если событие занимает больше одного дня
      if (info.event.end && info.event.start.getDate() !== info.event.end.getDate()) {
        // Проверяем, не заканчивается ли событие ровно в полночь следующего дня
        if (moment(info.event.end).hour() === 0 && moment(info.event.end).minute() === 0 && moment(info.event.end).second() === 0) {
          // Если событие заканчивается ровно в полночь, уменьшаем дату на один день
          eventData.end_time = moment(info.event.end).subtract(1, 'seconds').toISOString(); // Устанавливаем конец предыдущего дня (23:59:59)
        } else {
          eventData.end_time = info.event.end.toISOString(); // Время окончания события
        }
      }

      console.log("Start time:", info.event.start.toISOString());
      console.log("End time:", eventData.end_time); // Для отладки

      // Отправляем запрос на сервер для обновления события
      $.request('eventManagement::onUpdateEvent', {
        data: eventData,
        success: function (response) {
          if (!response.error) {
            toastr.success('Событие успешно обновлено.');
          } else {
            toastr.error('Ошибка при обновлении события.');
          }
        },
        error: function () {
          toastr.error('Произошла ошибка при обновлении события.');
        }
      });
    },

    // Клик по событию для открытия модального окна
    eventClick: function (info) {
      openDeleteModal(info.event.id);

      var startTime = info.event.start;
      if (startTime) {
        // Преобразуем время события из UTC в Asia/Bishkek
        var localTime = moment.utc(startTime).tz("Asia/Bishkek"); // Преобразуем из UTC

        // Форматируем время для input[type="datetime-local"]
        var formattedDate = localTime.format('YYYY-MM-DDTHH:mm');

        // Устанавливаем значение в поле input с id "event-time"
        $('#event-time').val(formattedDate);
      }
    }

  });

  // Функция для обновления события
  function updateEvent(event) {
    var eventId = event.extendedProps ? event.extendedProps.event_id : null;
    if (!eventId) {
      toastr.error('ID события отсутствует, обновление невозможно.');
      return;
    }

    // Преобразуем новое время начала и окончания события в UTC
    var newStartTime = moment(event.start).utc().toISOString();
    var newEndTime = event.end ? moment(event.end).utc().toISOString() : null;

    // Формируем данные для отправки на сервер
    var eventData = {
      event_id: eventId,         // ID события
      start_time: newStartTime,  // Новое время начала в UTC
      end_time: newEndTime,      // Новое время окончания в UTC (если есть)
      title: event.title,        // Название события
      color: event.backgroundColor // Цвет события
    };

    // Отправляем запрос на сервер для обновления события
    $.request('eventManagement::onUpdateEvent', {
      data: eventData,
      success: function (response) {
        if (!response.error) {
          toastr.success('Событие успешно обновлено.');
        } else {
          toastr.error('Ошибка при обновлении события.');
        }
      },
      error: function () {
        toastr.error('Произошла ошибка при обновлении события.');
      }
    });
  }

  calendar.render();

  // Добавляем событие в календарь (создание нового события)
  $('#add-new-event').click(function (e) {
    e.preventDefault();

    var val = $('#new-event').val();
    if (val.length === 0) {
      toastr.error('Пожалуйста, введите название события.');
      return;
    }

    // Преобразуем текущее время в UTC
    var startTime = moment().toISOString();

    var eventData = {
      title: val,
      start_time: startTime,  // Время в UTC
      color: currColor
    };

    // Отправляем данные на сервер
    $.request('eventManagement::onCreateEvent', {
      data: eventData,
      success: function (response) {
        if (!response.error && response.event_id) {
          toastr.success(response.message || 'Событие успешно создано.');

          var event = $('<div />');
          event.css({
            'background-color': currColor,
            'border-color': currColor,
            'color': '#fff'
          }).addClass('external-event');
          event.text(val);
          event.attr('data-event-id', response.event_id);
          event.data('eventObject', {
            title: val,
            id: response.event_id,
            backgroundColor: currColor,
            borderColor: currColor
          });

          $('#external-events').prepend(event);
          ini_events(event);
          $('#new-event').val('');
        } else {
          toastr.error(response.message || 'Ошибка при создании события.');
        }
      },
      error: function () {
        toastr.error('Произошла ошибка при сохранении события.');
      }
    });
  });

  /* Выбор цвета */
  $('#color-chooser > li > a').click(function (e) {
    e.preventDefault();
    currColor = $(this).css('color');
    $('#add-new-event').css({
      'background-color': currColor,
      'border-color': currColor
    });
  });

  /* Удаление событий */
  var eventIdToDelete = null;

  function openDeleteModal(eventId) {
    eventIdToDelete = eventId;
    $('#modal-warning').modal('show');
  }

  function deleteEvent() {
    if (!eventIdToDelete) {
      return;
    }

    $.request('eventManagement::onDeleteEvent', {
      data: { event_id: eventIdToDelete },
      success: function (response) {
        if (!response.error) {
          calendar.refetchEvents();
          toastr.success(response.message || 'Событие успешно удалено.');
        } else {
          toastr.error(response.message || 'Ошибка при удалении события.');
        }
        $('#modal-warning').modal('hide');
      },
      error: function () {
        toastr.error('Произошла ошибка при удалении события.');
        $('#modal-warning').modal('hide');
      }
    });

    eventIdToDelete = null;
  }

  $('#delete-event').last().click(function () {
    deleteEvent();
  });

  // Обработка кнопки "Сохранить" в модальном окне
  $('#save-event').click(function () {
    var eventTime = $('#event-time').val(); // Получаем новое время из поля

    if (!eventIdToDelete) {
      toastr.error('ID события отсутствует, обновление невозможно.');
      return;
    }

    // Получаем событие из календаря по его ID
    var calendarEvent = calendar.getEventById(eventIdToDelete);

    if (!calendarEvent || !calendarEvent.extendedProps.event_id) {
      toastr.error('Событие не найдено или ID отсутствует, обновление невозможно.');
      return;
    }

    var eventId = calendarEvent.extendedProps.event_id;

    // Преобразуем время начала события в ISO-формат
    var updatedStartTime = new Date(eventTime).toISOString();

    // Проверяем, существует ли end_time у события
    var existingEndTime = calendarEvent.end ? calendarEvent.end.toISOString() : null;

    // Формируем объект события для обновления
    var event = {
      extendedProps: {
        event_id: eventId
      },
      start: updatedStartTime, // Обновляем только start_time
      end: existingEndTime // Если end_time существует, сохраняем его
    };

    // Отправляем запрос на обновление события
    updateEvent(event); // Обновляем start_time на сервере

    // Закрываем модальное окно
    $('#modal-warning').modal('hide');
  });

});
