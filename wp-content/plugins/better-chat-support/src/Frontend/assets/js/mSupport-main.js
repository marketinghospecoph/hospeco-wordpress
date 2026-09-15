/**
 * Table of contents
 * -----------------------------------
 * 01.CURRENT TIME
 * 02.OPEN BUTTON
 * 03.CHECK AVAILABILITY
 * 04.GET WEEK DAY
 * 05.MULTI USER AVAILABILITY
 * 06.MULTI USER SEARCH
 * 07.BUTTONS AVAILABILITY
 * 08.SINGLE CHAT AVAILABILITY
 * DARK VERSION
 * -----------------------------------
 */

(function () {
"use strict";
let mSupport = document.querySelectorAll(".mSupport");
let mSupportMulti = document.querySelectorAll(".mSupport-multi");
let noBubble = document.querySelector(".no_bubble");
let mSupportBubble = document.querySelectorAll(".mSupport-bubble");
let currentTime = document.querySelector(".current-time");
let mSupportUserAvailability = document.querySelectorAll(
  ".mSupportUserAvailability",
);
let mSupportSendMessage = document.querySelector(".mSupport__send-message");
let mSupportMultiPopupContent = document.querySelector(
  ".mSupport-multi__popup--content",
);
let user = document.querySelector(".user");

/******************** 01.CURRENT TIME  ********************/

let today = new Date();
if (currentTime !== null) {
  let time =
    today.getHours() + ":" + today.getMinutes() + ":" + today.getSeconds();
  currentTime.innerText = time;
}
/******************** 02.OPEN BUTTON  ********************/
const openChatBtn = () => {
  mSupport.forEach((item) => {
    item.classList?.toggle("mSupport-show");
  });
  mSupportMulti.forEach((item) => {
    item.classList?.toggle("mSupport-show");
  });
};
mSupportBubble.forEach((item) => {
  if (!noBubble) {
    item.addEventListener("click", openChatBtn);
  }
});

if (alternativeMSupportBubble.length > 0) {
  const elements = document.querySelectorAll(alternativeMSupportBubble);
  elements.forEach((item) => {
    if (!noBubble) {
      item.addEventListener("click", openChatBtn);
    }
  });
}

/******************** 02b.HEADER CLOSE BUTTON  ********************/
document.querySelectorAll(".mSupport-popup-close").forEach((item) => {
  item.addEventListener("click", (e) => {
    e.preventDefault();
    [...mSupport, ...mSupportMulti].forEach((el) =>
      el.classList.remove("mSupport-show"),
    );
  });
});

/******************** AUTO OPEN POPUP  ********************/
(function () {
  const bubbles = document.querySelectorAll(
    ".mSupport[data-auto-open-timeout], .mSupport-multi[data-auto-open-timeout]"
  );
  bubbles.forEach((bubble) => {
    const ms = parseInt(bubble.getAttribute("data-auto-open-timeout"), 10);
    if (!isNaN(ms) && ms > 0) {
      setTimeout(() => bubble.classList.add("mSupport-show"), ms);
    }
  });
})();

/******************** 03.CHECK AVAILABILITY  ********************/
function is_available(available, now) {
  let is_available = false;
  let almost_available = false;
  for (let key in available) {
    if (available.hasOwnProperty(key)) {
      if (get_day_of_week(key) == now.day()) {
        let timeRange = available[key].split("-");
        let tz = now.tz();
        let start = tz ? moment.tz(timeRange[0], "HH:mm", tz) : moment(timeRange[0], "HH:mm");
        let end   = tz ? moment.tz(timeRange[1], "HH:mm", tz) : moment(timeRange[1], "HH:mm");
        // Align start/end to the same date as `now`
        start.year(now.year()).month(now.month()).date(now.date());
        end.year(now.year()).month(now.month()).date(now.date());

        if (now.isBetween(start, end)) {
          is_available = true;
        } else if (now.isBefore(start)) {
          almost_available = true;
        }
      }
    }
  }
  return { is_available: is_available, almost_available: almost_available };
}

/******************** 04.GET WEEK DAY  ********************/
function get_day_of_week(name) {
  name = name.toLowerCase();
  if (name == "sunday") {
    return 0;
  } else if (name == "monday") {
    return 1;
  } else if (name == "tuesday") {
    return 2;
  } else if (name == "wednesday") {
    return 3;
  } else if (name == "thursday") {
    return 4;
  } else if (name == "friday") {
    return 5;
  } else if (name == "saturday") {
    return 6;
  }
}

/******************** 05.MULTI USER AVAILABILITY  ********************/
const searchInfo = mSupportMultiPopupContent?.getAttribute("data-search");
const isGrid = document
  .querySelector(".mSupport-multi")
  ?.classList.contains("mSupport-grid");

if (mSupportUserAvailability !== undefined) {
  if (searchInfo === "true") {
    mSupportMultiPopupContent.classList.add("mSupport-search");
  }
  if (mSupportUserAvailability.length > 3 && !isGrid) {
    mSupportMultiPopupContent.classList.add("mSupport-scroll");
  }
  if (mSupportUserAvailability.length > 4 && isGrid) {
    mSupportMultiPopupContent.classList.add("mSupport-scroll");
  }
  mSupportUserAvailability.forEach((item) => {
    const availableTimes = item.getAttribute("data-useravailability");
    const timezone = item.getAttribute("data-timezone");
    let now = timezone ? moment().tz(timezone) : moment();
    let available = is_available(JSON.parse(availableTimes), now);

    if (available.is_available || availableTimes == null) {
      mSupportUserAvailability.forEach((item) => {
        const availableTime = item.getAttribute("data-useravailability");
        if (availableTime === availableTimes) {
          item.classList.add("avatar-active");
          item.classList.remove("avatar-inactive");
        }
      });
    } else {
      mSupportUserAvailability.forEach((item) => {
        const availableTime = item.getAttribute("data-useravailability");
        if (availableTime === availableTimes) {
          item.classList.add("avatar-inactive");
          item.setAttribute("disabled", "");
          item.classList.remove("avatar-active");
        }
      });
    }
  });
}
/******************** 06.MULTI USER SEARCH  ********************/
function searchUser() {
  var searchKeyword, i, txtValue;
  let input = document.getElementById("search-input");
  let filter = input.value.toUpperCase();
  let multiUser = document.getElementById("multi-user");
  let user = multiUser.getElementsByClassName("user");

  for (i = 0; i < user.length; i++) {
    searchKeyword = user[i].getElementsByClassName("user__info--name")[0];
    txtValue = searchKeyword.textContent || searchKeyword.innerText;
    if (txtValue.toUpperCase().indexOf(filter) > -1) {
      user[i].style.display = "";
    } else {
      user[i].style.display = "none";
    }
  }
}

/******************** 07.BUTTONS AVAILABILITY  ********************/
let mSupportButtons = document.querySelectorAll(".mSupportButtons");
if (mSupportButtons !== undefined) {
  mSupportButtons.forEach((item) => {
    const availableTimes = item.getAttribute("data-btnavailablety");
    const timezone = item.getAttribute("data-timezone");
    let now = timezone ? moment().tz(timezone) : moment();
    let available = is_available(JSON.parse(availableTimes), now);

    if (available.is_available) {
      mSupportButtons.forEach((item) => {
        const availableTime = item.getAttribute("data-btnavailablety");
        if (availableTime === availableTimes) {
          item.classList.add("avatar-active");
          item.classList.remove("avatar-inactive");
        }
      });
    } else {
      mSupportButtons.forEach((item) => {
        const availableTime = item.getAttribute("data-btnavailablety");
        if (availableTime === availableTimes) {
          item.classList.add("avatar-inactive");
          item.classList.remove("avatar-active");
        }
      });
    }
  });
}

/******************** 08.SINGLE CHAT AVAILABILITY  ********************/
const chatAvailability = document.querySelector(".chat-availability");
const subtitleEl = document.querySelector(".info__title");
const mSupport_agent = document.querySelector(".mSupport_agent");

if (chatAvailability) {
  const chatAvailableTime = chatAvailability.getAttribute("data-availability");
  if (chatAvailableTime !== undefined) {
    const timezone = chatAvailability.getAttribute("data-timezone");
    let now = timezone ? moment().tz(timezone) : moment();
    let available = is_available(JSON.parse(chatAvailableTime), now);

    if(mSupport_agent) {
      if (!available.is_available) {
        subtitleEl.textContent = subtitleEl.getAttribute("data-offline");
      } else {
        subtitleEl.textContent = subtitleEl.getAttribute("data-online");
      }
    }

    if (available.is_available || chatAvailableTime == null) {
      chatAvailability.classList.add("avatar-active");
      chatAvailability.classList.remove("avatar-inactive");
    } else {
      chatAvailability.classList.add("avatar-inactive");
      chatAvailability.classList.remove("avatar-active");
    }
  }
}

window.searchUser = searchUser;
})();
