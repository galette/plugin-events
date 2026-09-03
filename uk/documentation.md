---
title: Документація
description: Event and booking management
---

Це розширення надає:

* управління подіями,
* сполучення діяльності з подіями,
* управління бронюванням.

## Встановлення

Перш за все, завантажте розширення:

* [Get latest Events
  plugin!](https://github.com/galette-plugins/plugin-events/releases/latest)
* [Get Events plugin nightly
  build!](https://github.com/galette-plugins/plugin-events/releases/tag/nightly)

Витягніть завантажений архів у каталог Galette `plugins`. Наприклад, ось
вказівки під Linux (замініть `{url}` та `{version}` правильними значеннями):

```bash
$ cd /var/www/html/galette/plugins
$ wget {url}
$ tar xjvf galette-plugin-events-{version}.tar.bz2
```

## Започаткування бази даних

Для роботи цього розширення потрібно кілька таблиць у базі даних. Див [Інтерфейс
управління розширеннями
Galette](https://doc.galette.eu/en/master/plugins/index.html#plugins-managment).

Усе завершено. Розширення "Events" установлено :)

## Використання розширення

Коли розширення встановлено, під час входу користувача в систему в меню Galette
додається група `Events`. Існують різні можливості, які змінюються в залежності
від профілю користувача (простий користувач, менеджер групи, адміністратор,
...).

### Діяльності

Ви можете визначити стільки діяльностей, скільки хочете, і асоціювати їх з
якою-небудь подією. Діяльністю може бути організована поїздка, харчування,
проживання тощо.

![The list of activities](images/list_activities.png)

Діяльність складається з назви, стану та коментаря за бажанням.

Щоб додати нову діяльність, просто натисніть на посилання "Нова діяльність":

![The form of a new activity](images/new_activity.png)

### Події

Події є основною ціллю розширення. Ви можете визначити кілька відомостей, таких
як назва, дати початку і закінчення, місцеперебування тощо.

![The form of a new event](images/new_event.png)

Назва, дата початку та місто є обов'язковими. Всі інші відомості є цілком
необов'язковими.

Події, які пов'язані з групою, будуть доступні для всіх учасників. Якщо група
встановлена, то доступ до неї матимуть лише її члени та менеджери.

> **Note**
> 
> Коли менеджер групи створює нову подію, він повинен вибрати одну з груп, якими
> він володіє!

До кожної події можна прикріпити одну або кілька діяльностей, а також для
кожного набору, якщо він доступний, недоступний або навіть обов'язковий.
Виберіть подію для додавання та натисніть на кнопку.

![The activities attached to an event](images/event_activities.png)

> **Warning**
> 
> Додавання або видалення діяльності з події перезавантажить сторінку і
> попросить Вас заповнити обов'язкові відомості. Проте, (і це вказується кожен
> раз), подія **не зберігатиметься** під час цієї операції.
> 
> Обов’язково збережіть подію :)

From Events list, you can edit or remove entries, access to booking list or
export bookings as CSV.

![The list of events](images/events_list.png)

### Bookings

Bookings can be registered for each event. As we said before, simple members and
groups managers will be limited to their groups events, or to the events that
are not restricted to a group.

Adding a new booking can be achieved from the menu "New booking" or from the
event bookings list.

![The form of a new booking](images/new_booking.png)

Bookings are closed once the event is marked as close, or when the begin date is
over. Administrators and staff members can always add new bookings.

Activities list is retrieved from the event; mandatory ones must of course be
checked during booking.

![The list of bookings](images/bookings_list.png)

You can filter bookings list per event, payment type or payment status. You can
then send a mailing to booked members, using the standard Galette mailing
mechanism.
