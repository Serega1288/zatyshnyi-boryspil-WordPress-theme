document.documentElement.classList.add("js");

const themeConfig = window.zatyshnyiTheme || {};
const i18n = {
  openMenu: themeConfig.openMenu || "Відкрити меню",
  closeMenu: themeConfig.closeMenu || "Закрити меню",
  carousel: themeConfig.carousel || "карусель",
  defaultInterest: themeConfig.defaultInterest || "Квартира",
  defaultContext: themeConfig.defaultContext || "Підбір квартири",
  sending: themeConfig.sending || "Надсилаємо заявку…",
  sendFailed: themeConfig.sendFailed || "Не вдалося надіслати заявку. Спробуйте ще раз.",
  uncertain: themeConfig.uncertain || "Немає підтвердження від сервера. Дані залишилися у формі — повторіть надсилання.",
  success: themeConfig.success || "Дякуємо! Заявку №%s збережено. Наш менеджер зв’яжеться з вами.",
};

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
const leadSuccess = document.querySelector("[data-lead-success]");
const leadSuccessMessage = document.querySelector("[data-lead-success-message]");
const leadSubmit = document.querySelector("[data-lead-submit]");
const mobileMenuQuery = window.matchMedia("(max-width: 900px)");
const reducedMotionQuery = window.matchMedia("(prefers-reduced-motion: reduce)");
const backToTop = document.querySelector("[data-back-to-top]");
const currentDocumentUrl = new URL(window.location.href);
const navigationLinks = [...(menu?.querySelectorAll("a[href]") || [])].filter((link) => {
  const url = new URL(link.href, window.location.href);
  return Boolean(url.hash && url.origin === currentDocumentUrl.origin && url.pathname === currentDocumentUrl.pathname);
});
const navigationSections = navigationLinks
  .map((link) => ({ link, section: document.getElementById(link.hash.slice(1)) }))
  .filter(({ section }) => section);

let contactReturnTarget = null;
let leadReturnTarget = null;
let previousBodyPaddingRight = "";
let leadSuccessTimer = 0;
let pendingLead = null;

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
  menuButton?.setAttribute("aria-label", nextOpen ? i18n.closeMenu : i18n.openMenu);
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

const resetLeadSuccessState = () => {
  if (leadSuccessTimer) {
    window.clearTimeout(leadSuccessTimer);
    leadSuccessTimer = 0;
  }
  form?.classList.remove("is-success");
  if (leadSuccess) leadSuccess.hidden = true;
  if (leadSuccessMessage) leadSuccessMessage.textContent = "";
  if (status) {
    status.textContent = "";
    status.className = "form-status";
  }
};

const openLeadDialog = (trigger) => {
  if (!leadDialog || typeof leadDialog.showModal !== "function" || leadDialog.open) return;

  resetLeadSuccessState();

  if (trigger?.closest("[data-contact-popover]")) {
    leadReturnTarget = contactButtons.find((button) => !button.closest("[data-menu]")) || menuButton;
  } else {
    leadReturnTarget = trigger || document.activeElement;
  }
  const interest = trigger?.dataset.interest || i18n.defaultInterest;
  const context = trigger?.dataset.context || i18n.defaultContext;

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
  if (form?.classList.contains("is-success")) resetLeadSuccessState();
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
const apartmentControllers = new Map();

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
  if (isScrollable) slider.setAttribute("aria-roledescription", i18n.carousel);
  else slider.removeAttribute("aria-roledescription");
  document.querySelectorAll(`[aria-controls="${slider.id}"]`).forEach((button) => {
    if (button.hasAttribute("data-slider-prev")) button.disabled = slider.scrollLeft <= 2;
    if (button.hasAttribute("data-slider-next")) button.disabled = slider.scrollLeft >= maxScroll - 2;
  });
};

document.querySelectorAll(".apartments").forEach((section) => {
  const slider = section.querySelector(".apartment-list[data-slider]");
  const tabs = section.querySelector("[data-apartment-tabs]");
  const links = [...section.querySelectorAll("[data-apartment-tab]")];
  if (!slider || !tabs || !links.length) return;

  const isHorizontal = () => slider.scrollWidth > slider.clientWidth + 1;
  const setActiveTab = (cardId, { center = false } = {}) => {
    let activeLink = null;
    links.forEach((link) => {
      const isActive = decodeURIComponent(link.hash.slice(1)) === cardId;
      if (isActive) {
        link.setAttribute("aria-current", "true");
        activeLink = link;
      } else {
        link.removeAttribute("aria-current");
      }
    });

    if (!center || !activeLink) return;
    const tabsRect = tabs.getBoundingClientRect();
    const linkRect = activeLink.getBoundingClientRect();
    const maxLeft = Math.max(0, tabs.scrollWidth - tabs.clientWidth);
    const centeredLeft = tabs.scrollLeft + linkRect.left - tabsRect.left - (tabs.clientWidth - linkRect.width) / 2;
    tabs.scrollTo({
      left: Math.max(0, Math.min(maxLeft, centeredLeft)),
      behavior: reducedMotionQuery.matches ? "auto" : "smooth",
    });
  };

  const sync = () => {
    if (!isHorizontal()) return;
    const sliderLeft = slider.getBoundingClientRect().left;
    const cards = [...slider.querySelectorAll(".apartment-row")];
    const closestCard = cards.reduce((closest, card) => {
      if (!closest) return card;
      const currentDistance = Math.abs(card.getBoundingClientRect().left - sliderLeft);
      const closestDistance = Math.abs(closest.getBoundingClientRect().left - sliderLeft);
      return currentDistance < closestDistance ? card : closest;
    }, null);
    if (closestCard?.id) setActiveTab(closestCard.id, { center: true });
  };

  links.forEach((link) => {
    link.addEventListener("click", (event) => {
      const target = document.getElementById(decodeURIComponent(link.hash.slice(1)));
      if (!target || !section.contains(target)) return;
      setActiveTab(target.id, { center: true });
      if (!isHorizontal()) return;

      event.preventDefault();
      const sliderRect = slider.getBoundingClientRect();
      const targetRect = target.getBoundingClientRect();
      slider.scrollTo({
        left: slider.scrollLeft + targetRect.left - sliderRect.left,
        behavior: reducedMotionQuery.matches ? "auto" : "smooth",
      });
      history.replaceState(null, "", link.hash);
    });
  });

  apartmentControllers.set(slider, { sync });
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
      apartmentControllers.get(slider)?.sync();
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

const createRequestId = () => {
  if (window.crypto?.randomUUID) return window.crypto.randomUUID();
  return "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(/[xy]/g, (character) => {
    const random = Math.floor(Math.random() * 16);
    const value = character === "x" ? random : (random & 0x3) | 0x8;
    return value.toString(16);
  });
};

form?.addEventListener("submit", async (event) => {
  event.preventDefault();
  if (form.dataset.submitting === "true") return;
  if (!form.reportValidity()) return;

  const data = new FormData(form);
  const payload = {
    name: data.get("name"),
    phone: data.get("phone"),
    interest: data.get("interest"),
    message: data.get("message"),
    request_context: data.get("request_context"),
    apartment_type: data.get("apartment_type"),
    website: data.get("website"),
    source_url: window.location.href,
    language: themeConfig.language || "uk",
  };
  const signature = JSON.stringify(payload);
  if (pendingLead?.signature !== signature) {
    pendingLead = { signature, id: createRequestId() };
  }
  payload.request_id = pendingLead.id;

  const controls = [...form.querySelectorAll("input, select, textarea, button[type='submit']")];
  form.dataset.submitting = "true";
  controls.forEach((control) => { control.disabled = true; });
  if (status) {
    status.textContent = i18n.sending;
    status.className = "form-status is-progress";
  }

  const controller = new AbortController();
  const timeout = window.setTimeout(() => controller.abort(), 20000);

  try {
    if (!themeConfig.ajaxUrl || !themeConfig.leadNonce) {
      throw new Error(themeConfig.refresh || i18n.sendFailed);
    }

    const response = await fetch(themeConfig.ajaxUrl, {
      method: "POST",
      credentials: "same-origin",
      body: new URLSearchParams({
        action: "zb_submit_lead",
        nonce: themeConfig.leadNonce,
        payload: JSON.stringify(payload),
      }),
      signal: controller.signal,
    });
    const result = await response.json().catch(() => null);
    if (!response.ok || !result?.success) {
      throw new Error(result?.data?.message || i18n.sendFailed);
    }

    if (leadSuccessMessage) {
      leadSuccessMessage.textContent = i18n.success.replace("%s", String(result.data.lead_id));
    }
    form.reset();
    pendingLead = null;
    if (status) {
      status.textContent = "";
      status.className = "form-status";
    }
    form.classList.add("is-success");
    if (leadSuccess) leadSuccess.hidden = false;
    leadSuccessTimer = window.setTimeout(() => {
      leadSuccessTimer = 0;
      closeLeadDialog();
    }, 8000);
  } catch (error) {
    const uncertain = error?.name === "AbortError" || error?.name === "TypeError";
    if (status) {
      status.textContent = uncertain ? i18n.uncertain : (error?.message || i18n.sendFailed);
      status.className = "form-status is-error";
    }
  } finally {
    window.clearTimeout(timeout);
    form.dataset.submitting = "false";
    controls.forEach((control) => { control.disabled = false; });
    if (leadSubmit) leadSubmit.disabled = false;
  }
});
