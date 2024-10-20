// Функция для загрузки уведомлений
function loadNotifications() {
  console.log('Загрузка уведомлений...');  // Лог для проверки вызова функции

  $.request('onGetNotifications', {
    success: function (data) {
      console.log('Уведомления получены:', data);  // Логируем полученные уведомления
      
      const notificationList = document.getElementById('notification-list');

      if (!notificationList) {
        console.error('Элемент с ID notification-list не найден');
        return;
      }

      notificationList.innerHTML = ''; // Очищаем список перед добавлением новых уведомлений

      if (data.notifications && data.notifications.length > 0) {
        let notificationCounts = {
          patient_booked: 0,
          patient_attached: 0,
          event_created: 0,
          event_updated: 0,
          event_deleted: 0
        };

        let lastNotificationTime = {};

        // Логируем каждый тип уведомления
        data.notifications.forEach(notification => {
          console.log('Тип уведомления:', notification.type);  // Логируем тип каждого уведомления

          if (notification.type in notificationCounts) {
            notificationCounts[notification.type]++;
            lastNotificationTime[notification.type] = notification.time;  // Сохраняем время последнего уведомления
          } else {
            console.error('Неизвестный тип уведомления:', notification.type);  // Логирование для проверки
          }
        });

        console.log('notificationCounts:', notificationCounts);  // Логируем обновленные счётчики уведомлений

        // Формируем вывод для каждого типа уведомлений
        for (let type in notificationCounts) {
          if (notificationCounts[type] > 0) {
            let icon = '';
            let message = '';

            switch (type) {
              case 'patient_booked':
                console.log('Обрабатываем patient_booked');  // Лог для проверки обработки этого типа
                icon = '<i class="fas fa-user-plus mr-2"></i>';
                message = `${notificationCounts[type]} новых приемов`;
                break;
              case 'patient_attached':
                console.log('Обрабатываем patient_attached');  // Лог для проверки обработки этого типа
                icon = '<i class="fas fa-link mr-2"></i>';
                message = `${notificationCounts[type]} постоянных пациентов`;
                break;
              // Добавляем остальные типы уведомлений
              default:
                console.log('Обрабатываем уведомления другого типа:', type);  // Лог для проверки других типов
                icon = '<i class="fas fa-info-circle mr-2"></i>';
                message = `${notificationCounts[type]} уведомлений`;
                break;
            }

            notificationList.innerHTML += `
              <a href="${data.notifications[0].url}" class="dropdown-item">
                ${icon}
                <span class="text-muted text-xs">${message}</span>
                <span class="float-right text-muted text-xs mt-1">${lastNotificationTime[type]}</span>
              </a>
              <div class="dropdown-divider"></div>`;
          }
        }

        // Если ничего не добавилось, выводим сообщение, что уведомлений нет
        if (notificationList.innerHTML === '') {
          notificationList.innerHTML = '<div class="text-xs text-center pt-2 pb-2">Уведомления отсутствуют</div>';
        }
      } else {
        console.log('Нет новых уведомлений');
        notificationList.innerHTML = '<div class="text-xs text-center pt-2 pb-2">Уведомления отсутствуют</div>';
      }
    },
    error: function () {
      console.error('Ошибка при загрузке уведомлений');  // Логируем ошибку
    }
  });
}

// Вызов функции при загрузке страницы или нажатии на кнопку
$(document).ready(function () {
  console.log('Загрузка страницы, вызов loadNotifications()');  // Лог при загрузке страницы
  loadNotifications();  // Загрузка уведомлений при загрузке страницы
});

// Обновление счётчика непрочитанных уведомлений
function updateNotificationCount() {
  console.log('Обновление счётчика непрочитанных уведомлений');  // Лог вызова функции

  $.request('onGetUnreadCount', {
    success: function (data) {
      console.log('Количество непрочитанных уведомлений:', data.unreadCount);  // Лог количества
      $('#notification-count').text(data.unreadCount);
    },
    error: function () {
      console.error('Ошибка при обновлении счётчика непрочитанных уведомлений');  // Логируем ошибку
    }
  });
}

// Первичная загрузка данных
$(document).ready(function () {
  console.log('Первичная загрузка данных для счётчика уведомлений');  // Лог при загрузке страницы
  updateNotificationCount();

  // Обновляем каждые 30 секунд
  setInterval(updateNotificationCount, 30000);

  // Загрузка уведомлений при клике
  $('#notification-icon').on('click', function () {
    console.log('Клик на иконку уведомлений, вызов loadNotifications()');  // Лог при клике на иконку
    loadNotifications();
  });
});

// Обработка клика на уведомление для отметки его как прочитанного
$(document).on('click', '.notification-item-js', function (event) {
  const notificationId = $(this).data('id');
  console.log('Клик на уведомление, ID уведомления:', notificationId);  // Лог ID уведомления

  if (notificationId) {
    $.request('onMarkNotificationAsRead', {
      data: { notification_id: notificationId },
      success: function (response) {
        console.log('Уведомление отмечено как прочитанное');  // Лог при успешной отметке уведомления
        updateNotificationCount();
      },
      error: function () {
        console.error('Ошибка при пометке уведомления как прочитанное');  // Лог ошибки
      }
    });
  }
});
