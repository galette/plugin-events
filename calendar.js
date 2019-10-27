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
    validRange: function(nowDate) {
      return {
        start: nowDate
      };
    },
    views: _fullcalendar_views,
    header: {
      left: 'title',
      right: 'dayGridMonth,listDay,listWeek,listMonth prevYear,prev,today,next,nextYear'
    },
    locale: _fullcalendar_locale,
    weekNumbers: true,
    events: _calendar_dataurl
  });

  calendar.render();
});

