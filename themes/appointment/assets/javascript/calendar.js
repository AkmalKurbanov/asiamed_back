$(function () {
  var currColor = '#3c8dbc';

  var calendarEl = document.getElementById('calendar');

  // Инициализация календаря
  var calendar = new FullCalendar.Calendar(calendarEl, {
    locale: "ru",
    themeSystem: 'bootstrap',
    timeZone: 'local', // Установка локальной временной зоны
    editable: true,    // Включаем редактирование событий (перетаскивание и изменение размера)
    droppable: false,  // Убираем возможность перетаскивания внешних событий
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
              return event;
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

    // Обработка перетаскивания события внутри календаря
    eventDrop: function (info) {
      var eventId = info.event.extendedProps ? info.event.extendedProps.event_id : info.event.id;

      if (!eventId) {
        toastr.error('ID события отсутствует, обновление невозможно.');
        console.log('Ошибка: ID события отсутствует');
        return;
      }

      // Преобразуем новое время начала и окончания в UTC
      var newStartTime = moment(info.event.start).utc().toISOString();
      var newEndTime = info.event.end ? moment(info.event.end).utc().toISOString() : null;

      // Логирование для отладки
      console.log("Перетаскиваемое событие, ID:", eventId);
      console.log("Новое время начала (UTC):", newStartTime);
      console.log("Новое время окончания (UTC):", newEndTime);

      // Отправляем запрос на сервер для обновления события
      var eventData = {
        event_id: eventId,  // ID события для обновления
        start_time: newStartTime,
        end_time: newEndTime
      };

      $.request('eventManagement::onUpdateEvent', {
        data: eventData,
        success: function (response) {
          if (!response.error) {
            toastr.success('Событие успешно обновлено.');
            console.log('Событие обновлено успешно:', response);
          } else {
            toastr.error('Ошибка при обновлении события.');
            console.log('Ошибка на сервере при обновлении события:', response);
          }
        },
        error: function (error) {
          toastr.error('Произошла ошибка при обновлении события.');
          console.log('Ошибка запроса при обновлении события:', error);
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
        if (moment(info.event.end).hour() === 0 && moment(info.event.end).minute() === 0 && moment(info.event.end).second() === 0) {
          // Если событие заканчивается ровно в полночь, уменьшаем дату на один день
          eventData.end_time = moment(info.event.end).subtract(1, 'seconds').toISOString(); // Устанавливаем конец предыдущего дня (23:59:59)
        } else {
          eventData.end_time = info.event.end.toISOString();
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
        var localTime = moment.utc(startTime).tz("Asia/Bishkek");

        // Форматируем время для input[type="datetime-local"]
        var formattedDate = localTime.format('YYYY-MM-DDTHH:mm');
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

    var isAllDay = event.allDay;  // Проверяем, является ли событие событием на весь день
    var newStartTime = isAllDay ? moment(event.start).format('YYYY-MM-DD') : moment(event.start).utc().toISOString();
    var newEndTime = event.end ? (isAllDay ? moment(event.end).format('YYYY-MM-DD') : moment(event.end).utc().toISOString()) : null;

    var eventData = {
      event_id: eventId,
      start_time: newStartTime,
      end_time: newEndTime,
      allDay: isAllDay,
      title: event.title,
      color: event.backgroundColor
    };

    $.request('eventManagement::onUpdateEvent', {
      data: eventData,
      success: function (response) {
        if (!response.error) {
          toastr.success('Событие успешно обновлено.');
          calendar.refetchEvents();
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

    var isAllDay = $('#all-day-checkbox').is(':checked');  // Проверяем, выбрано ли событие на весь день
    var startTime = isAllDay ? moment().format('YYYY-MM-DD') : moment().toISOString();  // Форматируем дату в зависимости от типа события

    var eventData = {
      title: val,
      start_time: startTime,
      color: currColor,
      allDay: isAllDay  // Передаем выбор allDay
    };

    // Отправляем данные на сервер для создания события
    $.request('eventManagement::onCreateEvent', {
      data: eventData,
      success: function (response) {
        if (!response.error && response.event_id) {
          toastr.success('Событие успешно создано.');

          // Добавляем событие в календарь
          var event = {
            id: response.event_id,
            title: val,
            start: startTime,
            backgroundColor: currColor,
            borderColor: currColor,
            allDay: isAllDay,  // Передаем выбор allDay
            editable: true,
            durationEditable: true,
            extendedProps: {
              event_id: response.event_id
            }
          };

          calendar.addEvent(event);

          // Очищаем поле ввода и чекбокс после создания
          $('#new-event').val('');
          $('#all-day-checkbox').prop('checked', false);
        } else {
          toastr.error('Ошибка при создании события.');
        }
      },
      error: function () {
        toastr.error('Произошла ошибка при создании события.');
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
          // Получаем список всех событий в календаре
          var allEvents = calendar.getEvents();

          // Ищем событие по ID и удаляем его
          var calendarEvent = allEvents.find(function (event) {
            return event.extendedProps && event.extendedProps.event_id == eventIdToDelete;
          });

          if (calendarEvent) {
            calendarEvent.remove();  // Удаляем событие из календаря
          }

          toastr.success(response.message || 'Событие успешно удалено.');
          calendar.refetchEvents();
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

  $('#save-event').click(function () {
    var eventTime = $('#event-time').val();

    if (!eventIdToDelete) {
      toastr.error('ID события отсутствует, обновление невозможно.');
      return;
    }

    var calendarEvent = calendar.getEventById(eventIdToDelete);

    if (!calendarEvent || !calendarEvent.extendedProps.event_id) {
      toastr.error('Событие не найдено или ID отсутствует, обновление невозможно.');
      return;
    }

    var eventId = calendarEvent.extendedProps.event_id;
    var updatedStartTime = new Date(eventTime).toISOString();

    var existingEndTime = calendarEvent.end ? calendarEvent.end.toISOString() : null;

    var event = {
      extendedProps: {
        event_id: eventId
      },
      start: updatedStartTime,
      end: existingEndTime
    };

    // Обновляем событие
    updateEvent(event);

    // Закрываем модальное окно
    $('#modal-warning').modal('hide');
  });
});
