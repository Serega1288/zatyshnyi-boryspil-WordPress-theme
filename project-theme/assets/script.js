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
  success: themeConfig.success || "Ми отримали ваш запит. Менеджер відділу продажу ЖК «Затишний Бориспіль» зв’яжеться з вами найближчим часом.",
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
const leadFormFields = document.querySelector(".lead-form-fields");
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
let leadCloseTimer = 0;
let leadResetTimer = 0;
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
  if (leadResetTimer) {
    window.clearTimeout(leadResetTimer);
    leadResetTimer = 0;
  }
  form?.classList.remove("is-success");
  leadFormFields?.removeAttribute("inert");
  leadFormFields?.removeAttribute("aria-hidden");
  if (leadSuccess) leadSuccess.hidden = true;
  if (leadSuccessMessage) leadSuccessMessage.textContent = "";
  if (status) {
    status.textContent = "";
    status.className = "form-status";
  }
};

const openLeadDialog = (trigger) => {
  if (!leadDialog || typeof leadDialog.showModal !== "function" || leadDialog.open) return;

  if (leadCloseTimer) {
    window.clearTimeout(leadCloseTimer);
    leadCloseTimer = 0;
  }
  leadDialog.classList.remove("is-visible", "is-closing");
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
  const scrollPosition = { left: window.scrollX, top: window.scrollY };
  const previousScrollBehavior = document.documentElement.style.scrollBehavior;
  document.documentElement.style.scrollBehavior = "auto";
  leadDialog.showModal();

  // Native dialog autofocus can scroll to the dialog's DOM position near the footer.
  window.scrollTo(scrollPosition.left, scrollPosition.top);
  setModalScrollState(true);

  requestAnimationFrame(() => {
    leadDialog.classList.add("is-visible");
    leadClose?.focus({ preventScroll: true });
    document.documentElement.style.scrollBehavior = previousScrollBehavior;
  });
};

const closeLeadDialog = () => {
  if (!leadDialog?.open || leadDialog.classList.contains("is-closing")) return;
  if (reducedMotionQuery.matches) {
    leadDialog.close();
    return;
  }

  leadDialog.classList.remove("is-visible");
  leadDialog.classList.add("is-closing");
  leadCloseTimer = window.setTimeout(() => {
    leadCloseTimer = 0;
    if (leadDialog.open) leadDialog.close();
  }, 360);
};

leadOpeners.forEach((button) => {
  button.setAttribute("aria-haspopup", "dialog");
  button.setAttribute("aria-controls", "lead-dialog");
  button.addEventListener("click", () => openLeadDialog(button));
});
leadClose?.addEventListener("click", closeLeadDialog);
leadDialog?.addEventListener("cancel", (event) => {
  event.preventDefault();
  closeLeadDialog();
});

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
  if (leadCloseTimer) {
    window.clearTimeout(leadCloseTimer);
    leadCloseTimer = 0;
  }
  leadDialog.classList.remove("is-visible", "is-closing");
  setModalScrollState(false);
  if (form?.classList.contains("is-success")) {
    form.reset();
    resetLeadSuccessState();
  }
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
  const tabsContainer = section.querySelector("[role='tablist']");
  const tabs = [...section.querySelectorAll("[data-apartment-tab]")];
  const panels = tabs.map((tab) => {
    const panel = document.getElementById(tab.getAttribute("aria-controls") || "");
    return panel && section.contains(panel) ? panel : null;
  });
  const previousCategory = section.querySelector("[data-apartment-category-prev]");
  const nextCategory = section.querySelector("[data-apartment-category-next]");
  if (!tabsContainer || !tabs.length || panels.some((panel) => !panel)) return;

  let activeCategoryIndex = 0;

  const centerCategoryTab = (tab) => {
    const tabsRect = tabsContainer.getBoundingClientRect();
    const tabRect = tab.getBoundingClientRect();
    const maxLeft = Math.max(0, tabsContainer.scrollWidth - tabsContainer.clientWidth);
    const centeredLeft = tabsContainer.scrollLeft + tabRect.left - tabsRect.left - (tabsContainer.clientWidth - tabRect.width) / 2;
    tabsContainer.scrollTo({
      left: Math.max(0, Math.min(maxLeft, centeredLeft)),
      behavior: reducedMotionQuery.matches ? "auto" : "smooth",
    });
  };

  const activateCategory = (requestedIndex, { focus = false, updateHash = false, center = true } = {}) => {
    const index = Math.max(0, Math.min(tabs.length - 1, requestedIndex));
    activeCategoryIndex = index;

    tabs.forEach((tab, tabIndex) => {
      const isActive = tabIndex === index;
      tab.setAttribute("aria-selected", String(isActive));
      tab.tabIndex = isActive ? 0 : -1;
      panels[tabIndex].hidden = !isActive;
    });

    section.classList.add("is-tabs-ready");
    previousCategory?.toggleAttribute("disabled", index === 0);
    nextCategory?.toggleAttribute("disabled", index === tabs.length - 1);
    if (center) centerCategoryTab(tabs[index]);
    if (focus) tabs[index].focus({ preventScroll: true });
    if (updateHash) history.replaceState(null, "", `#${panels[index].id}`);
  };

  tabs.forEach((tab, index) => {
    tab.addEventListener("click", (event) => {
      event.preventDefault();
      activateCategory(index, { updateHash: true });
    });

    tab.addEventListener("keydown", (event) => {
      let nextIndex = null;
      if (event.key === "ArrowLeft") nextIndex = (index - 1 + tabs.length) % tabs.length;
      if (event.key === "ArrowRight") nextIndex = (index + 1) % tabs.length;
      if (event.key === "Home") nextIndex = 0;
      if (event.key === "End") nextIndex = tabs.length - 1;
      if (nextIndex === null) return;
      event.preventDefault();
      activateCategory(nextIndex, { focus: true, updateHash: true });
    });
  });

  previousCategory?.addEventListener("click", () => {
    activateCategory(activeCategoryIndex - 1, { updateHash: true });
    if (previousCategory.disabled) nextCategory?.focus({ preventScroll: true });
  });
  nextCategory?.addEventListener("click", () => {
    activateCategory(activeCategoryIndex + 1, { updateHash: true });
    if (nextCategory.disabled) previousCategory?.focus({ preventScroll: true });
  });

  const activateCategoryFromHash = () => {
    const hashId = decodeURIComponent(window.location.hash.slice(1));
    const hashIndex = panels.findIndex((panel) => panel.id === hashId);
    activateCategory(hashIndex >= 0 ? hashIndex : activeCategoryIndex, { center: hashIndex >= 0 });
    if (hashIndex >= 0) {
      requestAnimationFrame(() => panels[hashIndex].scrollIntoView({ block: "start" }));
    }
  };

  const initialHashId = decodeURIComponent(window.location.hash.slice(1));
  const initialIndex = panels.findIndex((panel) => panel.id === initialHashId);
  activateCategory(initialIndex >= 0 ? initialIndex : 0, { center: initialIndex >= 0 });
  if (initialIndex >= 0) {
    requestAnimationFrame(() => panels[initialIndex].scrollIntoView({ block: "start" }));
  }
  window.addEventListener("hashchange", activateCategoryFromHash);

  const galleryControllers = new Map();
  section.querySelectorAll("[data-apartment-plan-gallery]").forEach((gallery) => {
    const slides = [...gallery.querySelectorAll("[data-plan-slide]")];
    const thumbnails = [...gallery.querySelectorAll("[data-plan-thumbnail]")];
    const apartmentCta = gallery.closest("[data-apartment-panel]")?.querySelector(".apartment-card-cta[data-open-lead]");
    if (!slides.length) return;

    let activePlanIndex = 0;
    const setActivePlan = (requestedIndex, { focus = false, center = true } = {}) => {
      const index = Math.max(0, Math.min(slides.length - 1, requestedIndex));
      activePlanIndex = index;
      slides.forEach((slide, slideIndex) => {
        slide.hidden = slideIndex !== index;
      });
      thumbnails.forEach((thumbnail, thumbnailIndex) => {
        thumbnail.setAttribute("aria-pressed", String(thumbnailIndex === index));
      });

      if (apartmentCta) {
        const activePlan = slides[index].querySelector("[data-plan-open]");
        const planName = [activePlan?.dataset.planCode, activePlan?.dataset.planArea].filter(Boolean).join(", ");
        const baseContext = apartmentCta.dataset.apartmentBaseContext || "";
        const baseType = apartmentCta.dataset.apartmentBaseType || "";
        apartmentCta.dataset.context = planName ? [baseContext, planName].filter(Boolean).join(": ") : baseContext;
        apartmentCta.dataset.apartmentType = planName ? [baseType, planName].filter(Boolean).join(" — ") : baseType;
      }

      const activeThumbnail = thumbnails[index];
      if (activeThumbnail && center) {
        activeThumbnail.scrollIntoView({
          behavior: reducedMotionQuery.matches ? "auto" : "smooth",
          block: "nearest",
          inline: "center",
        });
      }
      if (activeThumbnail && focus) activeThumbnail.focus({ preventScroll: true });
    };

    thumbnails.forEach((thumbnail, index) => {
      thumbnail.addEventListener("click", () => setActivePlan(index));
      thumbnail.addEventListener("keydown", (event) => {
        let nextIndex = null;
        if (event.key === "ArrowLeft") nextIndex = (index - 1 + thumbnails.length) % thumbnails.length;
        if (event.key === "ArrowRight") nextIndex = (index + 1) % thumbnails.length;
        if (event.key === "Home") nextIndex = 0;
        if (event.key === "End") nextIndex = thumbnails.length - 1;
        if (nextIndex === null) return;
        event.preventDefault();
        setActivePlan(nextIndex, { focus: true });
      });
    });

    setActivePlan(0, { center: false });
    galleryControllers.set(gallery, {
      get activeIndex() { return activePlanIndex; },
      setActive: setActivePlan,
      slides,
    });
  });

  const planDialog = section.querySelector("[data-apartment-plan-dialog]");
  if (!planDialog) return;

  const dialogImage = planDialog.querySelector("[data-plan-dialog-image]");
  const dialogCode = planDialog.querySelector("[data-plan-dialog-code]");
  const dialogArea = planDialog.querySelector("[data-plan-dialog-area]");
  const dialogCounter = planDialog.querySelector("[data-plan-dialog-counter]");
  const dialogPrevious = planDialog.querySelector("[data-plan-dialog-prev]");
  const dialogNext = planDialog.querySelector("[data-plan-dialog-next]");
  const dialogClose = planDialog.querySelector("[data-plan-dialog-close]");
  const dialogFrame = planDialog.querySelector(".apartment-plan-dialog-frame");
  const dialogZoom = planDialog.querySelector("[data-plan-dialog-zoom]");
  const dialogZoomLabel = planDialog.querySelector("[data-plan-dialog-zoom-label]");
  let activeGallery = null;
  let activeDialogIndex = 0;
  let planDialogReturnTarget = null;
  let planDialogCloseTimer = 0;
  let planDialogBackdropStarted = false;

  const setPlanDialogZoom = (actualSize) => {
    planDialog.classList.toggle("is-actual-size", actualSize);
    dialogZoom?.setAttribute("aria-pressed", String(actualSize));
    if (dialogZoomLabel && dialogZoom) {
      dialogZoomLabel.textContent = actualSize ? dialogZoom.dataset.labelFit || "" : dialogZoom.dataset.labelActual || "";
    }
    requestAnimationFrame(() => {
      if (!dialogFrame) return;
      dialogFrame.scrollTo({
        left: actualSize ? Math.max(0, (dialogFrame.scrollWidth - dialogFrame.clientWidth) / 2) : 0,
        top: 0,
        behavior: "auto",
      });
    });
  };

  const renderPlanDialog = (requestedIndex) => {
    const controller = activeGallery ? galleryControllers.get(activeGallery) : null;
    if (!controller) return;
    const openers = [...activeGallery.querySelectorAll("[data-plan-open]")];
    if (!openers.length) return;

    activeDialogIndex = (requestedIndex + openers.length) % openers.length;
    controller.setActive(activeDialogIndex, { center: false });
    const opener = openers[activeDialogIndex];
    planDialogReturnTarget = opener;
    if (dialogImage) {
      dialogImage.src = opener.dataset.planFull || "";
      dialogImage.alt = opener.dataset.planAlt || "";
      dialogImage.hidden = false;
    }
    if (dialogCode) {
      dialogCode.textContent = opener.dataset.planCode || "";
      dialogCode.hidden = !dialogCode.textContent;
    }
    if (dialogArea) {
      dialogArea.textContent = opener.dataset.planArea || "";
      dialogArea.hidden = !dialogArea.textContent;
    }
    if (dialogCounter) dialogCounter.textContent = `${activeDialogIndex + 1} / ${openers.length}`;
    if (dialogPrevious) dialogPrevious.disabled = openers.length < 2;
    if (dialogNext) dialogNext.disabled = openers.length < 2;
  };

  const openPlanDialog = (opener) => {
    const gallery = opener.closest("[data-apartment-plan-gallery]");
    if (!gallery) return;
    if (typeof planDialog.showModal !== "function") return;
    if (planDialog.open) return;

    activeGallery = gallery;
    planDialogReturnTarget = opener;
    setPlanDialogZoom(false);
    renderPlanDialog(Number.parseInt(opener.dataset.planIndex || "0", 10));
    const scrollPosition = { left: window.scrollX, top: window.scrollY };
    const previousScrollBehavior = document.documentElement.style.scrollBehavior;
    document.documentElement.style.scrollBehavior = "auto";
    planDialog.classList.remove("is-visible", "is-closing");
    planDialog.showModal();
    window.scrollTo(scrollPosition.left, scrollPosition.top);
    setModalScrollState(true);
    requestAnimationFrame(() => {
      planDialog.classList.add("is-visible");
      dialogClose?.focus({ preventScroll: true });
      document.documentElement.style.scrollBehavior = previousScrollBehavior;
    });
  };

  const closePlanDialog = () => {
    if (!planDialog.open || planDialog.classList.contains("is-closing")) return;
    if (reducedMotionQuery.matches) {
      planDialog.close();
      return;
    }
    planDialog.classList.remove("is-visible");
    planDialog.classList.add("is-closing");
    planDialogCloseTimer = window.setTimeout(() => {
      planDialogCloseTimer = 0;
      if (planDialog.open) planDialog.close();
    }, 260);
  };

  section.querySelectorAll("[data-plan-open]").forEach((opener) => {
    opener.addEventListener("click", () => openPlanDialog(opener));
  });
  dialogPrevious?.addEventListener("click", () => renderPlanDialog(activeDialogIndex - 1));
  dialogNext?.addEventListener("click", () => renderPlanDialog(activeDialogIndex + 1));
  dialogZoom?.addEventListener("click", () => setPlanDialogZoom(dialogZoom.getAttribute("aria-pressed") !== "true"));
  dialogClose?.addEventListener("click", closePlanDialog);
  planDialog.addEventListener("cancel", (event) => {
    event.preventDefault();
    closePlanDialog();
  });
  planDialog.addEventListener("keydown", (event) => {
    if (event.key !== "ArrowLeft" && event.key !== "ArrowRight") return;
    if (planDialog.classList.contains("is-actual-size") && dialogFrame?.contains(event.target)) return;
    event.preventDefault();
    renderPlanDialog(activeDialogIndex + (event.key === "ArrowRight" ? 1 : -1));
  });
  planDialog.addEventListener("pointerdown", (event) => {
    planDialogBackdropStarted = event.target === planDialog;
  });
  planDialog.addEventListener("click", (event) => {
    const shouldClose = planDialogBackdropStarted && event.target === planDialog;
    planDialogBackdropStarted = false;
    if (shouldClose) closePlanDialog();
  });
  planDialog.addEventListener("close", () => {
    if (planDialogCloseTimer) {
      window.clearTimeout(planDialogCloseTimer);
      planDialogCloseTimer = 0;
    }
    planDialog.classList.remove("is-visible", "is-closing");
    if (dialogImage) {
      dialogImage.removeAttribute("src");
      dialogImage.hidden = true;
    }
    setPlanDialogZoom(false);
    setModalScrollState(false);
    if (planDialogReturnTarget?.isConnected) {
      requestAnimationFrame(() => planDialogReturnTarget?.focus({ preventScroll: true }));
    }
    activeGallery = null;
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
  form.setAttribute("aria-busy", "true");
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
      leadSuccessMessage.textContent = result?.data?.message || i18n.success;
    }
    pendingLead = null;
    if (status) {
      status.textContent = "";
      status.className = "form-status";
    }
    if (leadSuccess) leadSuccess.hidden = false;
    leadFormFields?.setAttribute("inert", "");
    leadFormFields?.setAttribute("aria-hidden", "true");
    requestAnimationFrame(() => {
      form.classList.add("is-success");
      leadSuccess?.focus({ preventScroll: true });
    });
    leadResetTimer = window.setTimeout(() => {
      leadResetTimer = 0;
      form.reset();
    }, reducedMotionQuery.matches ? 0 : 300);
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
    form.removeAttribute("aria-busy");
    controls.forEach((control) => { control.disabled = false; });
    if (leadSubmit) leadSubmit.disabled = false;
  }
});
