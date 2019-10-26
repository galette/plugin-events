import $ from 'jquery';
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import listPlugin from '@fullcalendar/list';

$(function() {
  var initialLocaleCode = 'fr';
  var localeSelectorEl = document.getElementById('locale-selector');
  var calendarEl = document.getElementById('calendar');

  var calendar = new Calendar(calendarEl, {
    plugins: [ interactionPlugin, dayGridPlugin, listPlugin ],
    views: {
      listDay: { buttonText: 'list day' },
      listWeek: { buttonText: 'list week' },
      listMonth: { buttonText: 'list month' },
      dayGridMonth: { buttonText: 'month calendar' },
    },
    header: {
      left: 'title',
      right: 'dayGridMonth ,listDay,listWeek,listMonth prevYear,prev,today,next,nextYear'
    },
    locale: initialLocaleCode,
    weekNumbers: true,
    events: 'https://fullCalendar.io/demo-events.json'
  });

  calendar.render();
});

