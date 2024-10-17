// Функция для загрузки уведомлений
function loadNotifications() {
  $.request('onGetNotifications', {
    success: function (data) {
      const notificationList = document.getElementById('notification-list');
      notificationList.innerHTML = ''; // Очищаем список перед добавлением новых уведомлений

      // Добавляем проверку, существует ли массив уведомлений
      if (data.notifications && data.notifications.length > 0) {
        data.notifications.forEach(notification => {
          notificationList.innerHTML += `
    <a href="${notification.url}" class="dropdown-item notification-item-js" data-id="${notification.id}">
      <i class="fas fa-bell mr-2"></i> ${notification.text}
      <span class="float-right text-muted text-sm">${notification.time}</span>
    </a>
    <div class="dropdown-divider"></div>`;
        });
      } else {
        notificationList.innerHTML = '<span class="dropdown-item">Уведомления отсутствуют</span>';
      }
    },
    error: function () {
      console.error('Ошибка загрузки уведомлений');
    }
  });
}

// Вызов функции при загрузке страницы или нажатии на кнопку
document.querySelector('.nav-link').addEventListener('click', function () {
  loadNotifications();
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
  event.preventDefault();

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
