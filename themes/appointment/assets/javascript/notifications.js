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
          patient_attached: 0
        };

        let lastNotificationTime = {};

        // Группируем уведомления по типу
        data.notifications.forEach(notification => {
          console.log('Тип уведомления:', notification.type);  // Логируем тип каждого уведомления

          if (notification.type in notificationCounts) {
            notificationCounts[notification.type]++;
            lastNotificationTime[notification.type] = notification.time;  // Сохраняем время последнего уведомления
          }
        });

        // Формируем вывод для каждого типа уведомлений
        for (let type in notificationCounts) {
          if (notificationCounts[type] > 0) {
            let icon = '';
            let message = '';
            let url = '';

            switch (type) {
              case 'patient_booked':
                icon = '<i class="fas fa-user-plus mr-2"></i>';
                message = `${notificationCounts[type]} новых приемов`;
                url = '/doctor-booked-patients';
                break;
              case 'patient_attached':
                icon = '<i class="fas fa-link mr-2"></i>';
                message = `${notificationCounts[type]} постоянных пациентов`;
                url = '/doctor-attached-patients';
                break;
            }

            // Исправление здесь: добавьте доступ к каждому уведомлению с использованием подходящей переменной
            let lastNotificationTimeForType = lastNotificationTime[type] || '';

            notificationList.innerHTML += `
      <a href="${url}" class="dropdown-item notification-item-js" data-type="${type}">
        ${icon}
        <span class="text-muted text-xs">${message}</span>
        <span class="float-right text-muted text-xs mt-1">${lastNotificationTimeForType}</span>
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

  // Загрузка уведомлений при клике
  $('#notification-icon').on('click', function () {
    console.log('Клик на иконку уведомлений, вызов loadNotifications()');  // Лог при клике на иконку
    loadNotifications();
  });
});

// Обновление счётчика непрочитанных уведомлений
function updateNotificationCount() {
  console.log('Обновление счётчика непрочитанных уведомлений');

  $.request('onGetUnreadCount', {
    success: function (data) {
      console.log('Количество непрочитанных уведомлений:', data.unreadCount);
      $('#notification-count').text(data.unreadCount); // Обновляем счётчик в интерфейсе
    },
    error: function () {
      console.error('Ошибка при обновлении счётчика непрочитанных уведомлений');
    }
  });
}

// Первичная загрузка данных
$(document).ready(function () {
  console.log('Первичная загрузка данных для счётчика уведомлений');  // Лог при загрузке страницы
  updateNotificationCount();

  // Обновляем каждые 30 секунд
  setInterval(updateNotificationCount, 30000);
});



// Пометка всех уведомлений как прочитанных по типу
function markNotificationsAsRead(type) {
  console.log(`Отправка запроса на сервер для пометки уведомлений типа ${type} как прочитанных`);

  $.request('onMarkNotificationsAsRead', {
    data: { type: type },
    success: function (response) {
      console.log(`Уведомления типа ${type} помечены как прочитанные.`);
      removeNotificationsFromList(type);
      updateNotificationCount();
    },
    error: function () {
      console.error(`Ошибка при пометке уведомлений типа ${type} как прочитанные`);
    }
  });
}

// Удаление уведомлений из списка после пометки их как прочитанных
function removeNotificationsFromList(type) {
  const notificationList = document.getElementById('notification-list');
  if (!notificationList) {
    console.error('Элемент с ID notification-list не найден');
    return;
  }

  const itemsToRemove = notificationList.querySelectorAll(`[data-type="${type}"]`);
  itemsToRemove.forEach(item => {
    console.log(`Удаляем уведомление с типом ${type}`);  // Лог удаления уведомлений
    item.remove();
  });
}





