document.addEventListener('DOMContentLoaded', function () {
  // Функция для загрузки уведомлений
  function loadNotifications() {
    $.request('onGetNotifications', {
      success: function (data) {
        const notificationList = document.getElementById('notification-list');
        notificationList.innerHTML = ''; // Очищаем список перед добавлением новых уведомлений

        if (data.notifications.length > 0) {
          data.notifications.forEach(notification => {
            notificationList.innerHTML += `
                            <a href="#" class="dropdown-item">
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

  // Функция для обновления количества непрочитанных уведомлений
  function updateNotificationCount() {
    $.request('onGetUnreadCount', {
      success: function (data) {
        document.getElementById('notification-count').textContent = data.unreadCount;
      },
      error: function () {
        console.error('Ошибка обновления счётчика уведомлений');
      }
    });
  }

  // Автоматическая загрузка уведомлений при клике на иконку
  document.querySelector('.nav-link').addEventListener('click', function () {
    loadNotifications();
  });

  // Обновление количества уведомлений каждые 30 секунд
  setInterval(updateNotificationCount, 30000);

  // Первичная загрузка счётчика уведомлений при загрузке страницы
  updateNotificationCount();
});
