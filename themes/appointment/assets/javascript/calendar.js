
$(function () {
  var currColor = '#3c8dbc'; // Начальный цвет для событий
  var calendar; // Переменная для календаря

  function initCalendar() {
    var calendarEl = document.getElementById('calendar');

    // Инициализация календаря
    calendar = new FullCalendar.Calendar(calendarEl, {
      locale: "ru",
      themeSystem: 'bootstrap',
      eventDisplay: 'block',
      nextDayThreshold: '23:59',
      editable: true,
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
            console.log('Данные, полученные с сервера:', data);

            if (data.error) {
              failureCallback();
              toastr.error('Не удалось загрузить события');
            } else {
              var events = data.events.map(function (event) {
                console.log('Обработанное событие:', event);

                var eventData = {
                  id: event.id,
                  title: event.title,
                  start: event.start, // Используем start
                  end: event.end,     // Используем end
                  backgroundColor: event.backgroundColor || currColor,
                  borderColor: event.borderColor || currColor,
                  editable: event.editable !== undefined ? event.editable : true,
                  extendedProps: {
                    event_id: event.id
                  }
                };

                // Преобразуем allDay в булевое значение
                eventData.allDay = event.allDay === true || event.allDay === "1" || event.allDay === 1;

                return eventData;
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
          return;
        }

        // Преобразуем новое время начала и окончания в UTC
        var newStartTime = moment(info.event.start).utc().toISOString();
        var newEndTime = info.event.end ? moment(info.event.end).utc().toISOString() : null;

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
            } else {
              toastr.error('Ошибка при обновлении события.');
            }
          },
          error: function (error) {
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
          end_time: info.event.end ? info.event.end.toISOString() : info.event.start.toISOString() // Используем start в случае, если end не установлен
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
      },

      // Клик по событию для открытия модального окна
      eventClick: function (info) {
        openDeleteModal(info.event.id);

        // Заполняем поля title и description
        $('#event-title').val(info.event.title); // Заполняем поле для названия события
        $('#event-description').val(info.event.extendedProps.description || '');

        var startTime = info.event.start;

        // Проверяем, является ли событие на весь день
        if (info.event.allDay) {
          // Скрываем поле выбора времени для событий на весь день
          $('#event-time').closest('.form-group').hide();
        } else {
          // Показываем поле выбора времени для обычных событий
          $('#event-time').closest('.form-group').show();

          if (startTime) {
            var localTime = moment.utc(startTime).tz("Asia/Bishkek");

            // Форматируем время для input[type="datetime-local"]
            var formattedDate = localTime.format('YYYY-MM-DDTHH:mm');
            $('#event-time').val(formattedDate);
          }
        }
      }

    });

    // Рендерим календарь
    calendar.render();
  }

  // Инициализируем календарь
  initCalendar();

  // Функция для создания нового события
  $('#add-new-event').click(function (e) {
    e.preventDefault();

    var val = $('#new-event').val();
    if (val.length === 0) {
      toastr.error('Пожалуйста, введите название события.');
      return;
    }

    var startTime = moment().toISOString(); // Текущее время
    var isAllDay = $('#all_day').is(':checked') ? 1 : 0;

    var eventData = {
      title: val,
      start_time: startTime,
      color: currColor,
      all_day: isAllDay // Передаем значение чекбокса
    };

    // Отправляем данные на сервер для создания события
    $.request('eventManagement::onCreateEvent', {
      data: eventData,
      success: function (response) {
        if (!response.error && response.event_id) {
          toastr.success('Событие успешно создано.');
          calendar.refetchEvents(); // Перезагрузка событий в календаре
          $('#new-event').val('');
          $('#all_day').prop('checked', false); // Сбрасываем чекбокс
        } else {
          toastr.error('Ошибка при создании события.');
        }
      },
      error: function () {
        toastr.error('Произошла ошибка при создании события.');
      }
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
          // Удаляем событие из календаря
          var calendarEvent = calendar.getEventById(eventIdToDelete);
          if (calendarEvent) {
            calendarEvent.remove();
          }

          // Обновляем календарь, перезагружая события
          calendar.refetchEvents();

          toastr.success(response.message || 'Событие успешно удалено.');
        } else {
          toastr.error('Ошибка при удалении события.');
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

  // Функция для обновления события при нажатии на #save-event
  $('#save-event').click(function () {
    var eventTime = $('#event-time').val();
    var eventTitle = $('#event-title').val(); // Получаем значение нового заголовка
    var eventDescription = $('#event-description').val(); // Получаем значение нового описания

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

    // Обновляем данные события в календаре
    calendarEvent.setStart(updatedStartTime);
    calendarEvent.setProp('title', eventTitle);  // Обновляем заголовок
    calendarEvent.setExtendedProp('description', eventDescription);  // Обновляем описание

    if (existingEndTime) {
      calendarEvent.setEnd(existingEndTime);
    }

    // Отправляем данные на сервер для обновления
    var eventData = {
      event_id: eventId,
      start_time: updatedStartTime,
      end_time: existingEndTime,
      title: eventTitle, // Передаем новый заголовок
      description: eventDescription // Передаем новое описание
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

    // Закрываем модальное окно
    $('#modal-warning').modal('hide');
  });

  // Функция для обновления события
  function updateEvent(event) {
    var eventId = event.extendedProps ? event.extendedProps.event_id : null;
    if (!eventId) {
      toastr.error('ID события отсутствует, обновление невозможно.');
      return;
    }

    var newStartTime = moment(event.start).utc().toISOString();
    var newEndTime = event.end ? moment(event.end).utc().toISOString() : null;

    var eventData = {
      event_id: eventId,
      start_time: newStartTime,
      end_time: newEndTime,
      title: event.title,
      color: event.backgroundColor
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
  }

  // Выбор цвета для события
  $('#color-chooser > li > a').click(function (e) {
    e.preventDefault();
    currColor = $(this).css('color');
    $('#add-new-event').css({
      'background-color': currColor,
      'border-color': currColor
    });
  });
});

