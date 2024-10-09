$(function () {
  /* initialize the external events */
  function ini_events(ele) {
    ele.each(function () {
      var eventObject = {
        title: $.trim($(this).text())
      }
      $(this).data('eventObject', eventObject)
      $(this).draggable({
        zIndex: 1070,
        revert: true,
        revertDuration: 0
      })
    })
  }

  ini_events($('#external-events div.external-event'))

  /* initialize the calendar */
  var date = new Date()
  var d = date.getDate(),
    m = date.getMonth(),
    y = date.getFullYear()

  var Calendar = FullCalendar.Calendar;
  var Draggable = FullCalendar.Draggable;

  var containerEl = document.getElementById('external-events');
  var checkbox = document.getElementById('drop-remove');
  var calendarEl = document.getElementById('calendar');

  new Draggable(containerEl, {
    itemSelector: '.external-event',
    eventData: function (eventEl) {
      return {
        title: eventEl.innerText,
        backgroundColor: window.getComputedStyle(eventEl, null).getPropertyValue('background-color'),
        borderColor: window.getComputedStyle(eventEl, null).getPropertyValue('background-color'),
        textColor: window.getComputedStyle(eventEl, null).getPropertyValue('color'),
      };
    }
  });

  var calendar = new Calendar(calendarEl, {
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    themeSystem: 'bootstrap',
    editable: true,
    droppable: true,
    events: [], // Добавьте ваши события здесь, если есть
    drop: function (info) {
      if (checkbox.checked) {
        info.draggedEl.parentNode.removeChild(info.draggedEl);
      }
    }
  });

  calendar.render();

  /* ADDING EVENTS */
  $('#event-form').on('submit', function (e) {
    e.preventDefault(); // Остановить обычное поведение формы

    // Получаем данные из формы
    var eventTitle = $('#new-event').val();

    $.ajax({
      url: '/events',
      type: 'POST',
      data: {
        title: eventTitle,
        _token: $('input[name="_token"]').val() // Добавьте CSRF токен
      },
      success: function (response) {
        if (response.success) {
          alert(response.message); // Успешное сообщение
          calendar.addEvent({
            title: eventTitle,
            start: new Date(), // Установите время начала события
            allDay: true // Измените при необходимости
          });
          $('#new-event').val(''); // Сброс поля ввода
        } else {
          alert(response.message); // Сообщение об ошибке
        }
      },
      error: function () {
        alert('Ошибка при добавлении события. Пожалуйста, попробуйте еще раз.');
      }
    });
  });
});
