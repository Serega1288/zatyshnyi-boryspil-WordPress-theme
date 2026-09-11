const header = document.querySelector("[data-header]");
const menuButton = document.querySelector("[data-menu-button]");
const menu = document.querySelector("[data-menu]");
const form = document.querySelector("[data-form]");
const status = document.querySelector("[data-status]");
const contactButtons = [...document.querySelectorAll("[data-contact-button]")];
const contactPopover = document.querySelector("[data-contact-popover]");
const contactClose = document.querySelector("[data-contact-close]");
const leadDialog = document.querySelector("[data-lead-dialog]");
const leadOpeners = [...document.querySelectorAll("[data-open-lead]")];
const leadClose = document.querySelector("[data-close-lead]");
const leadInterest = document.querySelector("[data-lead-interest]");
const leadContextOutput = document.querySelector("[data-lead-context-output]");
const leadContextInput = document.querySelector("[data-lead-context-input]");
const leadApartmentInput = document.querySelector("[data-lead-apartment-input]");
const mobileMenuQuery = window.matchMedia("(max-width: 900px)");
const reducedMotionQuery = window.matchMedia("(prefers-reduced-motion: reduce)");
const backToTop = document.querySelector("[data-back-to-top]");
const navigationLinks = [...(menu?.querySelectorAll('a[href^="#"]') || [])];
const navigationSections = navigationLinks
  .map((link) => ({ link, section: document.getElementById(link.hash.slice(1)) }))
  .filter(({ section }) => section);

let contactReturnTarget = null;
let leadReturnTarget = null;
let previousBodyPaddingRight = "";

const updateHeader = () => header?.classList.toggle("is-scrolled", window.scrollY > 12);
updateHeader();
window.addEventListener("scroll", updateHeader, { passive: true });

const updateBackToTop = () => {
  if (!backToTop) return;
  const visible = window.scrollY > Math.max(500, window.innerHeight * 0.65);
  backToTop.classList.toggle("is-visible", visible);
  backToTop.setAttribute("aria-hidden", String(!visible));
  backToTop.tabIndex = visible ? 0 : -1;
};

updateBackToTop();
window.addEventListener("scroll", updateBackToTop, { passive: true });
window.addEventListener("resize", updateBackToTop);
backToTop?.addEventListener("click", () => {
  window.scrollTo({ top: 0, behavior: reducedMotionQuery.matches ? "auto" : "smooth" });
});

let navigationFrame = 0;

const setActiveNavigation = (activeLink = null) => {
  navigationSections.forEach(({ link }) => {
    if (link === activeLink) link.setAttribute("aria-current", "location");
    else link.removeAttribute("aria-current");
  });
};

const updateActiveNavigation = () => {
  navigationFrame = 0;
  const readingLine = (header?.getBoundingClientRect().bottom || 0) + 24;
  const activeItem = navigationSections.find(({ section }) => {
    const rect = section.getBoundingClientRect();
    return rect.top <= readingLine && rect.bottom > readingLine;
  });

  setActiveNavigation(activeItem?.link);
};

const scheduleActiveNavigationUpdate = () => {
  if (navigationFrame) return;
  navigationFrame = requestAnimationFrame(updateActiveNavigation);
};

const setNavigationFromHash = () => {
  if (!window.location.hash) return;
  const hashTarget = document.getElementById(decodeURIComponent(window.location.hash.slice(1)));
  const activeItem = navigationSections.find(({ section }) => section === hashTarget || section.contains(hashTarget));
  if (activeItem) setActiveNavigation(activeItem.link);
};

window.addEventListener("scroll", scheduleActiveNavigationUpdate, { passive: true });
window.addEventListener("resize", scheduleActiveNavigationUpdate);
window.addEventListener("hashchange", () => {
  setNavigationFromHash();
  scheduleActiveNavigationUpdate();
});
window.addEventListener("load", scheduleActiveNavigationUpdate);
window.addEventListener("pageshow", scheduleActiveNavigationUpdate);
setNavigationFromHash();
requestAnimationFrame(updateActiveNavigation);

const setMenuState = (open, { returnFocus = false, moveFocus = false } = {}) => {
  const nextOpen = Boolean(open && mobileMenuQuery.matches);
  menuButton?.setAttribute("aria-expanded", String(nextOpen));
  menuButton?.setAttribute("aria-label", nextOpen ? "Закрити меню" : "Відкрити меню");
  menu?.classList.toggle("is-open", nextOpen);
  document.body.classList.toggle("menu-open", nextOpen);
  if (menu) {
    if (mobileMenuQuery.matches && !nextOpen) menu.setAttribute("inert", "");
    else menu.removeAttribute("inert");
  }
  if (nextOpen && moveFocus) {
    requestAnimationFrame(() => menu?.querySelector("a")?.focus({ preventScroll: true }));
  }
  if (!nextOpen && returnFocus) menuButton?.focus({ preventScroll: true });
};

setMenuState(false);

menuButton?.addEventListener("click", () => {
  const open = menuButton.getAttribute("aria-expanded") === "true";
  setMenuState(!open, { moveFocus: open === false });
});

menu?.querySelectorAll("a").forEach((link) => link.addEventListener("click", () => {
  setActiveNavigation(link);
  setMenuState(false);
}));

const setContactState = (open, { trigger = null, returnFocus = false } = {}) => {
  if (!contactPopover) return;
  const nextOpen = Boolean(open);

  if (nextOpen) {
    contactReturnTarget = trigger || document.activeElement;
    contactPopover.hidden = false;
    contactPopover.removeAttribute("inert");
    contactButtons.forEach((button) => button.setAttribute("aria-expanded", "true"));
    contactClose?.focus({ preventScroll: true });
    setMenuState(false);
    return;
  }

  contactPopover.hidden = true;
  contactPopover.setAttribute("inert", "");
  contactButtons.forEach((button) => button.setAttribute("aria-expanded", "false"));

  if (returnFocus && contactReturnTarget?.isConnected) {
    const target = mobileMenuQuery.matches && contactReturnTarget.closest?.("[data-menu]")
      ? menuButton
      : contactReturnTarget;
    target?.focus({ preventScroll: true });
  }
};

contactButtons.forEach((button) => button.addEventListener("click", () => {
  const isOpen = contactPopover && !contactPopover.hidden;
  setContactState(!isOpen, { trigger: button });
}));

contactClose?.addEventListener("click", () => setContactState(false, { returnFocus: true }));

document.addEventListener("pointerdown", (event) => {
  if (!contactPopover || contactPopover.hidden) return;
  if (contactPopover.contains(event.target) || contactButtons.some((button) => button.contains(event.target))) return;
  setContactState(false);
});

const setModalScrollState = (open) => {
  if (open && !document.body.classList.contains("modal-open")) {
    previousBodyPaddingRight = document.body.style.paddingRight;
    const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
    if (scrollbarWidth > 0) {
      const currentPadding = Number.parseFloat(getComputedStyle(document.body).paddingRight) || 0;
      document.body.style.paddingRight = `${currentPadding + scrollbarWidth}px`;
    }
    document.documentElement.classList.add("modal-open");
    document.body.classList.add("modal-open");
    return;
  }

  if (!open && document.body.classList.contains("modal-open")) {
    document.documentElement.classList.remove("modal-open");
    document.body.classList.remove("modal-open");
    document.body.style.paddingRight = previousBodyPaddingRight;
  }
};

const openLeadDialog = (trigger) => {
  if (!leadDialog || typeof leadDialog.showModal !== "function" || leadDialog.open) return;

  if (trigger?.closest("[data-contact-popover]")) {
    leadReturnTarget = contactButtons.find((button) => !button.closest("[data-menu]")) || menuButton;
  } else {
    leadReturnTarget = trigger || document.activeElement;
  }
  const interest = trigger?.dataset.interest || "Квартира";
  const context = trigger?.dataset.context || "Підбір квартири";

  if (leadInterest && [...leadInterest.options].some((option) => option.value === interest)) {
    leadInterest.value = interest;
  }
  if (leadContextOutput) leadContextOutput.textContent = context;
  if (leadContextInput) leadContextInput.value = context;
  if (leadApartmentInput) leadApartmentInput.value = trigger?.dataset.apartmentType || "";
  if (status) status.textContent = "";

  setContactState(false);
  setMenuState(false);
  setModalScrollState(true);
  leadDialog.showModal();
  requestAnimationFrame(() => leadClose?.focus({ preventScroll: true }));
};

const closeLeadDialog = () => {
  if (leadDialog?.open) leadDialog.close();
};

leadOpeners.forEach((button) => {
  button.setAttribute("aria-haspopup", "dialog");
  button.setAttribute("aria-controls", "lead-dialog");
  button.addEventListener("click", () => openLeadDialog(button));
});
leadClose?.addEventListener("click", closeLeadDialog);

let backdropPointerStarted = false;
leadDialog?.addEventListener("pointerdown", (event) => {
  backdropPointerStarted = event.target === leadDialog;
});
leadDialog?.addEventListener("click", (event) => {
  const shouldClose = backdropPointerStarted && event.target === leadDialog;
  backdropPointerStarted = false;
  if (shouldClose) closeLeadDialog();
});
leadDialog?.addEventListener("close", () => {
  setModalScrollState(false);
  if (leadReturnTarget?.isConnected) {
    requestAnimationFrame(() => leadReturnTarget?.focus({ preventScroll: true }));
  }
});

document.addEventListener("keydown", (event) => {
  if (event.key !== "Escape" || leadDialog?.open) return;
  if (contactPopover && !contactPopover.hidden) {
    event.preventDefault();
    setContactState(false, { returnFocus: true });
    return;
  }
  if (menuButton?.getAttribute("aria-expanded") === "true") {
    setMenuState(false, { returnFocus: true });
  }
});

mobileMenuQuery.addEventListener("change", () => setMenuState(false));

const reveals = document.querySelectorAll(".reveal");
if ("IntersectionObserver" in window && !window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
  const observer = new IntersectionObserver((entries) => entries.forEach((entry) => {
    if (!entry.isIntersecting) return;
    entry.target.classList.add("is-visible");
    observer.unobserve(entry.target);
  }), { threshold: 0.1 });
  reveals.forEach((item) => observer.observe(item));
} else {
  reveals.forEach((item) => item.classList.add("is-visible"));
}

const sliders = document.querySelectorAll("[data-slider]");
const apartmentsSlider = document.getElementById("apartments-slider");
const apartmentTabs = document.querySelector("[data-apartment-tabs]");
const apartmentTabLinks = [...document.querySelectorAll("[data-apartment-tab]")];

const scrollSlider = (slider, direction) => {
  const firstCard = slider.firstElementChild;
  const gap = Number.parseFloat(getComputedStyle(slider).columnGap) || 0;
  const distance = (firstCard?.getBoundingClientRect().width || slider.clientWidth * 0.86) + gap;
  slider.scrollBy({
    left: distance * direction,
    behavior: reducedMotionQuery.matches ? "auto" : "smooth",
  });
};

const updateSliderControls = (slider) => {
  const maxScroll = Math.max(0, slider.scrollWidth - slider.clientWidth);
  const isScrollable = maxScroll > 1;
  slider.tabIndex = isScrollable ? 0 : -1;
  if (isScrollable) slider.setAttribute("aria-roledescription", "карусель");
  else slider.removeAttribute("aria-roledescription");
  document.querySelectorAll(`[aria-controls="${slider.id}"]`).forEach((button) => {
    if (button.hasAttribute("data-slider-prev")) button.disabled = slider.scrollLeft <= 2;
    if (button.hasAttribute("data-slider-next")) button.disabled = slider.scrollLeft >= maxScroll - 2;
  });
};

const isHorizontalApartmentSlider = () => apartmentsSlider
  && apartmentsSlider.scrollWidth > apartmentsSlider.clientWidth + 1;

const setActiveApartmentTab = (cardId, { center = false } = {}) => {
  let activeLink = null;

  apartmentTabLinks.forEach((link) => {
    const isActive = link.hash === `#${cardId}`;
    if (isActive) {
      link.setAttribute("aria-current", "true");
      activeLink = link;
    } else {
      link.removeAttribute("aria-current");
    }
  });

  if (!center || !activeLink || !apartmentTabs) return;

  const tabsRect = apartmentTabs.getBoundingClientRect();
  const linkRect = activeLink.getBoundingClientRect();
  const maxLeft = Math.max(0, apartmentTabs.scrollWidth - apartmentTabs.clientWidth);
  const centeredLeft = apartmentTabs.scrollLeft
    + linkRect.left
    - tabsRect.left
    - (apartmentTabs.clientWidth - linkRect.width) / 2;

  apartmentTabs.scrollTo({
    left: Math.max(0, Math.min(maxLeft, centeredLeft)),
    behavior: reducedMotionQuery.matches ? "auto" : "smooth",
  });
};

const syncApartmentTabToSlider = () => {
  if (!isHorizontalApartmentSlider()) return;

  const sliderLeft = apartmentsSlider.getBoundingClientRect().left;
  const cards = [...apartmentsSlider.querySelectorAll(".apartment-row")];
  const closestCard = cards.reduce((closest, card) => {
    if (!closest) return card;
    const currentDistance = Math.abs(card.getBoundingClientRect().left - sliderLeft);
    const closestDistance = Math.abs(closest.getBoundingClientRect().left - sliderLeft);
    return currentDistance < closestDistance ? card : closest;
  }, null);

  if (closestCard?.id) setActiveApartmentTab(closestCard.id, { center: true });
};

apartmentTabLinks.forEach((link) => {
  link.addEventListener("click", (event) => {
    const target = document.querySelector(link.hash);
    if (!target || !apartmentsSlider) return;

    setActiveApartmentTab(target.id, { center: true });
    if (!isHorizontalApartmentSlider()) return;

    event.preventDefault();
    const sliderRect = apartmentsSlider.getBoundingClientRect();
    const targetRect = target.getBoundingClientRect();

    apartmentsSlider.scrollTo({
      left: apartmentsSlider.scrollLeft + targetRect.left - sliderRect.left,
      behavior: reducedMotionQuery.matches ? "auto" : "smooth",
    });
    history.replaceState(null, "", link.hash);
  });
});

sliders.forEach((slider) => {
  slider.addEventListener("keydown", (event) => {
    if (event.key !== "ArrowLeft" && event.key !== "ArrowRight") return;
    if (slider.scrollWidth <= slider.clientWidth + 1) return;
    event.preventDefault();
    scrollSlider(slider, event.key === "ArrowRight" ? 1 : -1);
  });

  let activePointer = null;
  let startX = 0;
  let startScroll = 0;
  let dragged = false;

  slider.addEventListener("pointerdown", (event) => {
    if (event.pointerType !== "mouse" || event.button !== 0 || slider.scrollWidth <= slider.clientWidth + 1) return;
    activePointer = event.pointerId;
    startX = event.clientX;
    startScroll = slider.scrollLeft;
    dragged = false;
    slider.classList.add("is-dragging");
    slider.setPointerCapture?.(event.pointerId);
  });

  slider.addEventListener("pointermove", (event) => {
    if (activePointer !== event.pointerId) return;
    const delta = event.clientX - startX;
    if (Math.abs(delta) > 4) {
      dragged = true;
      event.preventDefault();
    }
    slider.scrollLeft = startScroll - delta;
  });

  const finishDrag = (event) => {
    if (activePointer !== event.pointerId) return;
    activePointer = null;
    slider.classList.remove("is-dragging");
    if (slider.hasPointerCapture?.(event.pointerId)) slider.releasePointerCapture(event.pointerId);
    requestAnimationFrame(() => updateSliderControls(slider));
  };

  slider.addEventListener("pointerup", finishDrag);
  slider.addEventListener("pointercancel", finishDrag);
  slider.addEventListener("lostpointercapture", finishDrag);
  slider.addEventListener("click", (event) => {
    if (!dragged) return;
    event.preventDefault();
    event.stopPropagation();
    dragged = false;
  }, true);

  let scrollFrame = 0;
  slider.addEventListener("scroll", () => {
    cancelAnimationFrame(scrollFrame);
    scrollFrame = requestAnimationFrame(() => {
      updateSliderControls(slider);
      if (slider === apartmentsSlider) syncApartmentTabToSlider();
    });
  }, { passive: true });

  updateSliderControls(slider);
});

document.querySelectorAll("[data-slider-prev], [data-slider-next]").forEach((button) => {
  button.addEventListener("click", () => {
    const sliderId = button.getAttribute("data-slider-prev") || button.getAttribute("data-slider-next");
    const slider = document.getElementById(sliderId);
    if (!slider) return;
    scrollSlider(slider, button.hasAttribute("data-slider-next") ? 1 : -1);
  });
});

window.addEventListener("resize", () => sliders.forEach(updateSliderControls));

form?.addEventListener("submit", (event) => {
  event.preventDefault();
  if (!form.checkValidity()) return form.reportValidity();
  if (status) status.textContent = "Форму заповнено. Підключіть обробник заявок перед публікацією.";
});
