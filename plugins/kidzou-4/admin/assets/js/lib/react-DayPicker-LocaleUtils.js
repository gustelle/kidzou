"use strict";


var DayPickerLocaleUtils = {

  formatMonthTitle: function formatMonthTitle(date) {
    var locale = arguments.length <= 1 || arguments[1] === undefined ? "en" : arguments[1];
    moment.locale(locale);
    return moment(date).format("MMMM YYYY");
  },

  formatWeekdayShort: function formatWeekdayShort(day) {
    var locale = arguments.length <= 1 || arguments[1] === undefined ? "en" : arguments[1];
    moment.locale(locale);
    return moment().weekday(day).format("dd");
  },

  formatWeekdayLong: function formatWeekdayLong(day) {
    var locale = arguments.length <= 1 || arguments[1] === undefined ? "en" : arguments[1];
    moment.locale(locale);
    return moment().weekday(day).format("dddd");
  },

  getFirstDayOfWeek: function getFirstDayOfWeek() {
    var locale = arguments.length <= 0 || arguments[0] === undefined ? "en" : arguments[0];
    moment.locale(locale);
    var localeData = moment.localeData(locale);
    return localeData.firstDayOfWeek();
  },

  getMonths: function getMonths() {
    var locale = arguments.length <= 0 || arguments[0] === undefined ? "en" : arguments[0];
    moment.locale(locale);
    var months = [];
    var i = 0;
    while (i < 12) {
      months.push(moment().month(i++).format("MMMM"));
    }
    return months;
  }

};
