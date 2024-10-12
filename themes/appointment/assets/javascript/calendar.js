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
  var calendar = new Calendar(calendarEl, {
    locale: "ru",
    themeSystem: 'bootstrap',
    editable: true,
    droppable: true,
    eventDisplay: 'block',
    events: function (fetchInfo, successCallback, failureCallback) {
      $.request('eventManagement::onLoadEvents', {
        success: function (data) {
          if (data.error) {
            failureCallback();
            toastr.error('Не удалось загрузить события');
          } else {
            successCallback(data.events);
          }
        },
        error: function () {
          failureCallback();
          toastr.error('Произошла ошибка при загрузке событий');
        }
      });
    },
    eventAdd: function (info) {
      // Проверяем, есть ли у события ID
      if (info.event.extendedProps && info.event.extendedProps.event_id && info.el) {
        var element = info.el;
        element.setAttribute('data-event-id', info.event.extendedProps.event_id);
        console.log('ID события присвоен:', info.event.extendedProps.event_id);
      } else {
        console.error('Элемент или ID события отсутствуют, присвоение невозможно.');
      }
    },

    drop: function (info) {
      var eventId = info.draggedEl.getAttribute('data-event-id');
      if (!eventId) {
        toastr.error('ID события отсутствует, обновление невозможно.');
        return;
      }

      var eventData = {
        event_id: eventId,
        start_time: info.dateStr
      };

      $.request('eventManagement::onUpdateEvent', {
        data: eventData,
        success: function (response) {
          if (!response.error) {
            toastr.success(response.message || 'Событие успешно обновлено.');
          } else {
            toastr.error(response.message || 'Ошибка при обновлении события.');
          }
        }
      });

      if ($('#drop-remove').is(':checked')) {
        info.draggedEl.parentNode.removeChild(info.draggedEl);
      }
    },
    eventClick: function (info) {
      openDeleteModal(info.event.id);

      // Получаем время начала события
      var startTime = info.event.start;
      if (startTime) {
        // Форматируем время в 'yyyy-MM-ddTHH:mm' для input[type="datetime-local"]
        var formattedDate = startTime.toISOString().slice(0, 16);
        $('#event-time').val(formattedDate); // Устанавливаем значение в поле input
      }
      
    },
    eventDrop: function (info) {
      updateEvent(info.event);
    },
    eventResize: function (info) {
      updateEvent(info.event);
    }
  });

  calendar.render();

  // Добавляем событие в календарь
  $('#add-new-event').click(function (e) {
    e.preventDefault();

    var val = $('#new-event').val();
    if (val.length === 0) {
      toastr.error('Пожалуйста, введите название события.');
      return;
    }

    var eventData = {
      title: val,
      start_time: new Date().toISOString(),
      color: currColor
    };

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

  // Функция для обновления события
  function updateEvent(event) {
    var eventId = event.extendedProps ? event.extendedProps.event_id : null;
    if (!eventId) {
      console.error('ID события отсутствует, обновление невозможно.');
      toastr.error('ID события отсутствует, обновление невозможно.');
      return;
    }

    $.request('eventManagement::onUpdateEvent', {
      data: {
        event_id: eventId,
        title: event.title,
        start_time: event.start ? event.start.toISOString() : null,
        end_time: event.end ? event.end.toISOString() : null,
        color: event.backgroundColor
      },
      success: function (response) {
        if (!response.error) {
          toastr.success(response.message || 'Событие успешно обновлено.');
        } else {
          toastr.error(response.message || 'Ошибка при обновлении события.');
        }
      },
      error: function () {
        toastr.error('Произошла ошибка при обновлении события.');
      }
    });
  }

  // Обработка кнопки "Сохранить"
  $('#save-event').click(function () {
    var eventTime = $('#event-time').val(); // Получаем новое время

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

    var event = {
      extendedProps: {
        event_id: eventId
      },
      start: { toISOString: () => eventTime } // Используем новое время для обновления
    };

    updateEvent(event); // Обновляем start_time
  });

    
});
