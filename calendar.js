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
    height: 'parent',
    locales: allLocales,
    locale: _fullcalendar_locale,
    weekNumbers: true,
    events: _calendar_dataurl,
    selectable: true,
    eventClick: function(info) {
      console.log(info);
      console.log(calendar.getDate());
      var _infos = JSON.parse(JSON.stringify(info.event.extendedProps));
      _infos.url = _calendar_event_url.replace('PLACEBO', _infos.id_event)
      var _elt = $('<div id="event_info" title="' + _infos.name + ' (' + _infos.begin_date_fmt + ' - ' + _infos.end_date_fmt + ')">' + _infos.description + '</div>');
      _elt.appendTo('body').dialog({
        modal: true,
        width: '30%',
        minWidth: 300,
        buttons: {
          Ok: function() {
            $(this).dialog( "close" );
          }
        },
        close: function(event, ui){
          _elt.remove();
        }
      });
      $('#event_link').attr('href', _infos.url);
    },
    eventRender: function(info) {
      if (_events.indexOf(info.event.extendedProps.id_event) == -1) {
        var _el = $(info.el);
        //FIXME: does not work :(
        var tooltip = _el.tooltip({
          content: function() {
            return info.event.extendedProps.description;
          }
        });
        _events.push(info.event.extendedProps.id_event);
      }
    }
  });

  calendar.render();
});

