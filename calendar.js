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
      _infos.url = _calendar_event_url.replace('PLACEBO', _infos.id_event);
      _infos.booking = _calendar_booking_url.replace('PLACEBO', _infos.id_event);
      _booking_action = function() {
          window.location.href = _infos.booking;
      };
      if (_modal_actions.length == 2) {
        _modal_actions[0].click = _booking_action;
      } else {
        _modal_actions[1].click = _booking_action;
      }
      var _elt = $('<div class="ui tiny modal"><div class="header">' + _infos.name + ' (' + _infos.begin_date_fmt + ' - ' + _infos.end_date_fmt + ')</div><div class="content">' + _infos.description + '</div></div>');
      _elt.appendTo('body');
      _elt.modal({
        onApprove: function() {
          window.location.href = _infos.url;
        },
        actions: _modal_actions
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

