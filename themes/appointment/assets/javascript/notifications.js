// Функция для загрузки уведомлений
function loadNotifications() {
  console.log('Запуск функции loadNotifications');  // Добавлено логгирование
  $.request('onGetNotifications', {
    success: function (data) {
      console.log(data);  // Посмотри, что вернёт сервер
      const notificationList = document.getElementById('notification-list');
      notificationList.innerHTML = ''; // Очищаем список перед добавлением новых уведомлений

      if (data.notifications && data.notifications.length > 0) {
        console.log('Найдено уведомлений:', data.notifications.length);  // Проверяем, сколько уведомлений пришло
        let notificationCounts = {
          patient_attached: 0,
          event_created: 0,
          event_updated: 0,
          event_deleted: 0
        };

        let lastNotificationTime = {};

        data.notifications.forEach(notification => {
          if (!notification.is_read) {
            notificationCounts[notification.type]++;
            lastNotificationTime[notification.type] = notification.time;
          }
        });

        for (let type in notificationCounts) {
          if (notificationCounts[type] > 0) {
            let icon = '';
            let message = '';

            switch (type) {
              case 'patient_attached':
                icon = '<i class="fas fa-user-plus mr-2"></i>';
                message = `${notificationCounts[type]} новых пациентов`;
                break;
              // Добавь остальные типы уведомлений сюда.
            }

            notificationList.innerHTML += `
              <a href="#" class="dropdown-item">
                ${icon}
                <span class="text-muted text-sm">${message}</span>
                <span class="float-right text-muted text-xs">${lastNotificationTime[type]}</span>
              </a>
              <div class="dropdown-divider"></div>`;
          }
        }

        if (notificationList.innerHTML === '') {
          notificationList.innerHTML = '<div class="text-sm text-center pt-2 pb-2">Уведомления отсутствуют</div>';
        }
      } else {
        console.log('Нет новых уведомлений');
        notificationList.innerHTML = '<div class="text-sm text-center pt-2 pb-2">Уведомления отсутствуют</div>';
      }
    },
    error: function () {
      console.error('Ошибка при загрузке уведомлений');
    }
  });
}


// Вызов функции при загрузке страницы или нажатии на кнопку
$(document).ready(function () {
  console.log('Страница загружена');
  loadNotifications();  // Загрузка уведомлений при загрузке страницы
});

// Обновление счётчика непрочитанных уведомлений
function updateNotificationCount() {
  $.request('onGetUnreadCount', {
    success: function (data) {
      $('#notification-count').text(data.unreadCount);
    }
  });
}

// Первичная загрузка данных
$(document).ready(function () {
  updateNotificationCount();

  // Обновляем каждые 30 секунд
  setInterval(updateNotificationCount, 30000);

  // Загрузка уведомлений при клике
  $('#notification-icon').on('click', function () {
    loadNotifications();
  });
});


$(document).on('click', '.notification-item-js', function (event) {
  const notificationId = $(this).data('id');
  console.log('Notification ID:', notificationId); // Логируй ID уведомления для проверки

  if (notificationId) {
    $.request('onMarkNotificationAsRead', {
      data: { notification_id: notificationId },
      success: function (response) {
        console.log('Уведомление помечено как прочитанное');
        updateNotificationCount();
      },
      error: function () {
        console.error('Ошибка при пометке уведомления как прочитанное');
      }
    });
  }
});
