

// Self-invoking function to encapsulate the code and avoid polluting the global namespace
(function (Calendar) {
  var cal; // Declaration of a variable to store the calendar instance

  // Constants
  var CALENDAR_CSS_PREFIX = 'toastui-calendar-'; // Constant for CSS class prefix
  var cls = function (className) { // Function to concatenate the CSS prefix with a class name
    return CALENDAR_CSS_PREFIX + className;
  };

  // DOM element selections using jQuery-style syntax
  //select the element with the class of 'navbar--range'
  var navbarRange = $('.navbar--range'); // Selects the range display element in the navbar
  var prevButton = $('.prev'); // Selects the previous button
  var nextButton = $('.next'); // Selects the next button
  var todayButton = $('.today'); // Selects the today button
  var dropdown = $('.dropdown'); // Selects the dropdown menu
  var dropdownTrigger = $('.dropdown-trigger'); // Selects the dropdown trigger
  var dropdownTriggerIcon = $('.dropdown-icon'); // Selects the icon in the dropdown trigger
  var dropdownContent = $('.dropdown-content'); // Selects the content of the dropdown
  var checkboxCollapse = $('.checkbox-collapse'); // Selects the checkbox for collapsing events
  var sidebar = $('.sidebar'); // Selects the sidebar

 /* The appState object in this web application is used to store and manage the current state 
 of the application. This includes keeping track of user interactions, the status of UI components 
 like dropdowns, and any dynamic data relevant to the application's functionality. For complex 
 applications with more intricate state management needs, frameworks like React are often used. 
 However, this implementation uses vanilla JavaScript to manage state. */
//  var appState = {
//    // go over every element in the eventTypes array and we call each element (object in this case)
//   // calendar and we return each calendar's id, we store the ids in the activeCalendarIds array
//   activeCalendarIds: eventTypes.map(function (calendar) { 
//     return calendar.id;
//   }),
//   // Boolean property to track the state (open or closed) of a dropdown UI component
//   isDropdownActive: false,
// };


// Function to reload events in the calendar
function reloadEvents() {
  var randomEvents;

  // Clears the current events from the calendar.
  // This is essential to avoid duplication of events and to ensure that the calendar
  // is updated with the most current information before adding new events.
  cal.clear();

  // Generates random events based on the current view of the calendar and the date range being displayed.
  // The function 'generateRandomEvents' presumably creates event data that is suitable for the current
  // context of the calendar, such as creating events specific to a particular month, week, or day view.
  // This function is likely tailored to mimic dynamic content and would be replaced in a real-world
  // scenario with a function fetching actual events from a database or an API.
  randomEvents = generateRandomEvents(
      cal.getViewName(),
      cal.getDateRangeStart(),
      cal.getDateRangeEnd()
  );
  // Adds the newly generated random events to the calendar.
  // This function updates the calendar's display with the new events, ensuring that the calendar
  // shows the latest and relevant event data according to its current view and date range.
  cal.createEvents(randomEvents);
}


  // Function to get a human-readable name for the calendar view type.
  // This is used to convert internal view type identifiers ('month', 'week', 'day')
  // into a format that is more understandable for users.
function getReadableViewName(viewType) {
  switch (viewType) {
      case 'month': return 'Monthly'; // Returns 'Monthly' for 'month' view type
      case 'week': return 'Weekly'; // Returns 'Weekly' for 'week' view type
      case 'day': return 'Daily'; // Returns 'Daily' for 'day' view type
      default: 
            // Throws an error if an unsupported or undefined view type is encountered.
            // This is important for debugging and ensuring that only valid view types are processed.
          throw new Error('no view type');
    }
  }

  // Function to display the range of dates currently being rendered in the calendar in the navbar.
  // This helps users understand the time period they are looking at in the calendar.
function displayRenderRange() {
  var rangeStart = cal.getDateRangeStart(); // Gets the start date of the currently displayed range.
  var rangeEnd = cal.getDateRangeEnd(); // Gets the end date of the currently displayed range.

    // Calls getNavbarRange (a presumably predefined function) to format the date range into a readable string,
    // and then updates the navbarRange element's text content with this string.
    // The formatted string helps users quickly see the date range they are viewing.
  navbarRange.textContent = getNavbarRange(rangeStart, rangeEnd, cal.getViewName());
  }

  // Function to set the text of the dropdown trigger based on the current calendar view.
  // This function updates the UI to reflect the current state of the calendar.
function setDropdownTriggerText() {
  var viewName = cal.getViewName(); // Retrieves the current view type of the calendar (e.g., 'month', 'week', 'day').
  var buttonText = $('.dropdown .button-text'); // Selects the button text element within the dropdown UI component.

    // Updates the text of the dropdown button to reflect the current view in a human-readable format.
    // This enhances the user interface by clearly indicating the current calendar view.
  buttonText.textContent = getReadableViewName(viewName);
  }

  // Function to toggle the dropdown's open/close state.
  // This function is responsible for changing the visual display of the dropdown,
  // making it an essential part of the interactive user experience.
function toggleDropdownState() {
  appState.isDropdownActive = !appState.isDropdownActive; // Toggles the dropdown's active state in the application's state.

    // Adds or removes the 'is-active' CSS class to the dropdown based on its active state.
    // This class is typically used to show or hide the dropdown content.
  dropdown.classList.toggle('is-active', appState.isDropdownActive);

    // Adds or removes the 'open' CSS class to the dropdown trigger icon, typically to change its appearance.
    // For example, this could be used to change the icon from a 'hamburger' to a 'close' icon.
  dropdownTriggerIcon.classList.toggle(cls('open'), appState.isDropdownActive);
  }
// Function to set the checked state of all checkboxes in the sidebar.
// This is typically used to toggle the selection of multiple calendar events or categories.
function setAllCheckboxes(checked) {
  // Selects all checkbox elements located within elements with the class '.sidebar-item'.
 var checkboxes = $$('.sidebar-item > input[type="checkbox"]');

  // Iterates over each checkbox to update its state.
  checkboxes.forEach(function (checkbox) {
      checkbox.checked = checked; // Sets or unsets the checkbox depending on the 'checked' parameter.

      // Calls setCheckboxBackgroundColor for each checkbox to visually reflect the change.
      // This helps maintain a consistent and intuitive UI, especially in terms of accessibility.
      setCheckboxBackgroundColor(checkbox);
  });
}

// Function to dynamically set the background color of a checkbox based on its checked state.
// This enhances the UI by providing visual feedback associated with each calendar.
// function setCheckboxBackgroundColor(checkbox) {
//   var calendarId = checkbox.value; // Retrieves the value associated with the checkbox, representing the calendar ID.

//   // Selects the label element immediately following the checkbox.
//   // This label is assumed to be related to the checkbox visually.
//   var label = checkbox.nextElementSibling;

//   // Attempts to find the calendar's information from eventTypes using the calendar ID.
//   // eventTypes is assumed to be an array of calendar objects with id and backgroundColor properties.
//   var calendarInfo = eventTypes.find(function (calendar) {
//       return calendar.id === calendarId;
//   });

//   // Sets a default background color if no specific calendar info is found.
//   // This ensures there is always a visual indicator even if the data is missing or incorrect.
//   if (!calendarInfo) {
//       calendarInfo = { backgroundColor: '#2a4fa7' };
//   }

//   // Applies the background color to the label using a CSS custom property.
//   // The color changes depending on whether the checkbox is checked or not,
//   // providing a visual cue about the checkbox state.
//   label.style.setProperty(
//       '--checkbox-' + calendarId,
//       checkbox.checked ? calendarInfo.backgroundColor : '#fff'
//   );
// }

// Function to update various aspects of the calendar UI.
// This function serves as a central point to refresh the UI components related to the calendar view.
function update() {
  setDropdownTriggerText(); // Updates the text of the dropdown trigger to reflect the current calendar view.
  displayRenderRange(); // Updates the display showing the current date range visible in the calendar.
  reloadEvents(); // Reloads and displays the events on the calendar based on the current view and date range.
}

// Function to bind application-wide events
function bindAppEvents() {
// Attaches an event listener to the dropdown trigger for handling clicks.
// When clicked, it will toggle the visibility state of the dropdown menu.
dropdownTrigger.addEventListener('click', toggleDropdownState);

// Attaches an event listener to the 'previous' button.
// On click, it navigates the calendar to the previous period (e.g., previous month, week, or day)
// and updates the view to reflect this change.
prevButton.addEventListener('click', function () {
    cal.prev(); // Moves the calendar to the previous period.
    update(); // Calls the update function to refresh the calendar display.
});

// Attaches an event listener to the 'next' button.
// This listener allows navigation to the next calendar period and updates the view accordingly.
nextButton.addEventListener('click', function () {
    cal.next(); // Moves the calendar to the next period.
    update(); // Updates the calendar display to show the new period.
});

// Attaches an event listener to the 'today' button.
// Clicking this button will reset the calendar view to the current date.
todayButton.addEventListener('click', function () {
    cal.today(); // Sets the calendar view to the current date.
    update(); // Updates the calendar display.
});

// Event listener for handling clicks within the dropdown content.
// This is typically used for changing the calendar view (e.g., month, week, day).
dropdownContent.addEventListener('click', function (e) {
    var targetViewName;

    // Checks if the clicked element has a 'viewName' data attribute.
    // If so, it changes the calendar view to the specified one.
    if ('viewName' in e.target.dataset) {
        targetViewName = e.target.dataset.viewName;
        cal.changeView(targetViewName); // Changes the calendar to the selected view.
        checkboxCollapse.disabled = targetViewName === 'month'; // Disables the checkbox if the month view is selected.
        toggleDropdownState(); // Toggles the dropdown's open/close state.
        update(); // Updates the calendar view.
    }
});

// Event listener for the checkbox that collapses duplicate events in week view.
// It dynamically adjusts the calendar's options based on the checkbox's state.
checkboxCollapse.addEventListener('change', function (e) {
    if ('checked' in e.target) {
        cal.setOptions({
            week: { collapseDuplicateEvents: !!e.target.checked },
            useDetailPopup: !e.target.checked,
        });
    }
});

// Event listener for sidebar clicks.
// This is primarily used for handling the visibility of calendar elements (e.g., showing or hiding specific calendars).
sidebar.addEventListener('click', function (e) {
    if ('value' in e.target) {
        // Handles the 'select all' or individual calendar selection functionality.
        if (e.target.value === 'all') {
            // Toggles the visibility of all calendars based on the current state.
            if (appState.activeCalendarIds.length > 0) {
                cal.setCalendarVisibility(appState.activeCalendarIds, false); // Hides all calendars.
                appState.activeCalendarIds = []; // Clears the list of active calendar IDs.
                setAllCheckboxes(false); // Unchecks all checkboxes.
            } else {
                // If no calendars are currently active, activates and shows all.
                appState.activeCalendarIds = eventTypes.map(function (calendar) {
                    return calendar.id;
                });
                cal.setCalendarVisibility(appState.activeCalendarIds, true); // Shows all calendars.
                setAllCheckboxes(true); // Checks all checkboxes.
            }
        } else {
            // Toggles the visibility of an individual calendar based on its checkbox state.
            if (appState.activeCalendarIds.indexOf(e.target.value) > -1) {
                // If the calendar is currently active, hides it and updates its state.
                appState.activeCalendarIds.splice(appState.activeCalendarIds.indexOf(e.target.value), 1);
                cal.setCalendarVisibility(e.target.value, false);
                setCheckboxBackgroundColor(e.target); // Updates the checkbox background color.
            } else {
                // If the calendar is not active, shows it and updates its state.
                appState.activeCalendarIds.push(e.target.value);
                cal.setCalendarVisibility(e.target.value, true);
                setCheckboxBackgroundColor(e.target); // Updates the checkbox background color.
            }
        }
    }
});
}


  // Function to bind instance-specific events to the calendar
function bindInstanceEvents() {
// Setting up various event handlers on the calendar instance
cal.on({
    // Logs information when the 'more events' button is clicked
    clickMoreEventsBtn: function (btnInfo) {
        console.log('clickMoreEventsBtn', btnInfo);
    },
    // Logs information when an individual event on the calendar is clicked
    clickEvent: function (eventInfo) {
        console.log('clickEvent', eventInfo);
    },
    // Logs information when a day name (e.g., Monday, Tuesday) is clicked
    clickDayName: function (dayNameInfo) {
        console.log('clickDayName', dayNameInfo);
    },
    // Logs information when a specific date or time slot is selected
    selectDateTime: function (dateTimeInfo) {
        console.log('selectDateTime', dateTimeInfo);
    },
    // Executed before creating a new event, logs the event, assigns a unique ID, and refreshes the calendar
    beforeCreateEvent: function (event) {
        console.log('beforeCreateEvent', event);
        event.id = chance.guid(); // Assigns a unique ID to the event using 'chance.guid()'
        cal.createEvents([event]); // Adds the new event to the calendar
        cal.clearGridSelections(); // Clears any selections on the calendar grid
    },
    // Executed before updating an event, logs the change, and updates the event in the calendar
    beforeUpdateEvent: function (eventInfo) {
        var event, changes;
        console.log('beforeUpdateEvent', eventInfo);
        event = eventInfo.event; // The original event
        changes = eventInfo.changes; // The changes to be applied to the event
        cal.updateEvent(event.id, event.calendarId, changes); // Updates the event with the new changes
    },
    // Executed before deleting an event, logs the event info, and then deletes it
    beforeDeleteEvent: function (eventInfo) {
        console.log('beforeDeleteEvent', eventInfo);
        cal.deleteEvent(eventInfo.id, eventInfo.calendarId); // Deletes the event from the calendar
    },
});
}

// Initializes the checkboxes in the application, setting their background color appropriately
function initCheckbox() {
var checkboxes = $$('input[type="checkbox"]'); // Selects all checkboxes
checkboxes.forEach(function (checkbox) {
    setCheckboxBackgroundColor(checkbox); // Sets the background color for each checkbox
});
}

// Function to generate the HTML template for calendar events
function getEventTemplate(event, isAllday) {
var html = [];
var start = moment(event.start.toDate().toUTCString()); // Converts the start time of the event to a moment object for formatting
if (!isAllday) {
    html.push('<strong>' + start.format('HH:mm') + '</strong> '); // Adds the start time to the template for non-all-day events
}
// Adds icons and labels depending on the type of the event (private, recurring, etc.)
if (event.isPrivate) {
    html.push('<span class="calendar-font-icon ic-lock-b"></span> Private');
} else {
    if (event.recurrenceRule) {
        html.push('<span class="calendar-font-icon ic-repeat-b"></span>');
    } else if (event.attendees.length > 0) {
        html.push('<span class="calendar-font-icon ic-user-b"></span>');
    } else if (event.location) {
        html.push('<span class="calendar-font-icon ic-location-b"></span>');
    }
    html.push(' ' + event.title); // Adds the event title
}
return html.join(''); // Returns the complete HTML string
}

// Initializes the calendar instance with specific options
cal = new Calendar('#app', {
calendars: eventTypes, // The array of calendar data to be displayed
useFormPopup: true, // Configuration to use a form popup for event creation and editing
useDetailPopup: true, // Configuration to use a detail popup for showing event details
eventFilter: function (event) {
    // Function to filter events based on the current calendar view
    var currentView = cal.getViewName(); // Gets the current view of the calendar
    // Filters events for the 'month' view
    if (currentView === 'month') {
        return ['allday', 'time'].includes(event.category) && event.isVisible;
    }
    return event.isVisible; // Default filter for other views
},
template: {
    // Defines templates for rendering all-day events and timed events
    allday: function (event) {
        return getEventTemplate(event, true);
    },
    time: function (event) {
        return getEventTemplate(event, false);
    },
},
});

// Calling functions to initialize the application
bindInstanceEvents(); // Binds instance-specific events to the calendar
bindAppEvents(); // Binds application-wide events
initCheckbox(); // Initializes the checkboxes
update(); // Updates the calendar view
})(tui.Calendar); // Immediately-invoked function expression (IIFE) to encapsulate the code
