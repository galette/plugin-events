import $ from 'jquery';
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import listPlugin from '@fullcalendar/list';
import allLocales from '@fullcalendar/core/locales-all';

$(function() {
  var initialLocaleCode = 'fr';
  var localeSelectorEl = document.getElementById('locale-selector');
  var calendarEl = document.getElementById('calendar');
  var _events = [];

  var calendar = new Calendar(calendarEl, {
    plugins: [ interactionPlugin, dayGridPlugin, listPlugin ],
    views: _fullcalendar_views,
    headerToolbar: {
      left: 'title',
      right: 'dayGridMonth,listDay,listWeek,listMonth prevYear,prev,today,next,nextYear'
    },
    height: 'auto',
    locales: allLocales,
    locale: _fullcalendar_locale,
    weekNumbers: true,
    events: _calendar_dataurl,
    selectable: true,
    eventClick: function(info) {
      var _infos = JSON.parse(JSON.stringify(info.event.extendedProps));
      _infos.url = _calendar_event_url.replace('PLACEBO', _infos.id_event)

      var _elt = $('<div class="ui modal"><div class="header">' + _infos.name + ' (' + _infos.begin_date_fmt + ' - ' + _infos.end_date_fmt + ')</div><div class="content">' + _infos.description + '</div></div>');
      _elt.appendTo('body');
      _elt.modal({
        onVisible: function() {
          $('#event_link').attr('href', _infos.url);
        }
      }).modal('show');
    },
    eventMouseEnter: function(info) {
      var _el = $(info.el);
      _el.popup({
        exclusive: true,
        hoverable: true,
        variation: 'basic',
        html: info.event.extendedProps.description
      }).popup('show');
    }
  });

  calendar.render();
});

