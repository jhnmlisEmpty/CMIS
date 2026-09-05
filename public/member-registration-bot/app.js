const API_ENDPOINT = "/api/member-registrations";
const ADDRESS_API = "/api/addresses";

const copy = {
    en: {
        documentTitle: "Member Registration Assistant | TVWHC Pangasinan",
        assistantName: "Registration assistant", assistantStatus: "Ready to help", restart: "Start over",
        continue: "Continue", back: "Back", skip: "Skip this optional question", optional: "Optional",
        edit: "Edit", editAnswers: "Edit answers", submit: "Submit registration", submitting: "Submitting...",
        select: "Select an option", loading: "Loading options...", retry: "Try loading again", skipped: "Not provided",
        review: "Please review your information before sending it to the church team.",
        consent: "I confirm that these details are accurate and consent to their use for church membership administration.",
        consentError: "Please confirm your consent before submitting.",
        networkError: "We could not connect to the server. Please try again.",
        submitError: "We could not submit your registration. Please try again.",
        addressLoadError: "We could not load the address list. Check your connection and try again.",
        successTitle: "Registration received",
        successCopy: "Thank you. A church administrator will review your information. The team will contact you through the email or phone number you provided after the review is complete.",
        reference: "Reference",
        questions: {
            name: "Welcome. What is your full name?",
            email: "What email address can the church use to contact you?",
            gender: "How should your gender be recorded?",
            birthdate: "What is your birthdate? This helps us identify your member record accurately.",
            phone: "What phone number may we use to reach you?",
            address_choice: "Would you like to add your current Philippine address?",
            region_code: "Select your region.", province_code: "Select your province.",
            city_code: "Select your city or municipality.", barangay_code: "Select your barangay.",
            street_address: "Enter your house number, building, or street."
        },
        labels: {
            name: "Full name", email: "Email", gender: "Gender", birthdate: "Birthdate", phone: "Phone", address_choice: "Add address",
            region_code: "Region", province_code: "Province", city_code: "City / municipality",
            barangay_code: "Barangay", street_address: "Street address"
        },
        placeholders: {
            name: "Your full name", email: "you@example.com", phone: "+63 917 123 4567",
            street_address: "House number, building, or street"
        },
        options: { female: "Female", male: "Male", yes: "Yes, add my address", no: "No, skip my address" },
        errors: {
            name: "Enter your first and last name using letters only.", email: "Please enter a valid email address.",
            gender: "Please select a gender.", birthdateRequired: "Please enter your birthdate.",
            birthdatePast: "Birthdate must be before today.", phone: "Phone number must be 20 characters or fewer.",
            addressChoice: "Please choose whether to add an address.", addressOption: "Please select an address option.",
            street: "Street address must be 255 characters or fewer."
        }
    },
    fil: {
        documentTitle: "Tulong sa Pagpaparehistro ng Miyembro | TVWHC Pangasinan",
        assistantName: "Registration assistant", assistantStatus: "Handang tumulong", restart: "Magsimula muli",
        continue: "Magpatuloy", back: "Bumalik", skip: "Laktawan ang opsyonal na tanong", optional: "Opsyonal",
        edit: "Baguhin", editAnswers: "Baguhin ang sagot", submit: "Isumite ang registration", submitting: "Isinusumite...",
        select: "Pumili", loading: "Kinukuha ang mga pagpipilian...", retry: "Subukang kunin muli", skipped: "Hindi ibinigay",
        review: "Pakisuri ang iyong impormasyon bago ito ipadala sa church team.",
        consent: "Kinukumpirma kong tama ang mga detalyeng ito at pumapayag akong gamitin ang mga ito para sa pangangasiwa ng church membership.",
        consentError: "Pakikumpirma ang iyong pahintulot bago magsumite.",
        networkError: "Hindi makakonekta sa server. Pakisubukan muli.",
        submitError: "Hindi maisumite ang registration. Pakisubukan muli.",
        addressLoadError: "Hindi makuha ang listahan ng address. Suriin ang koneksyon at subukan muli.",
        successTitle: "Natanggap ang registration",
        successCopy: "Salamat. Susuriin ng church administrator ang iyong impormasyon. Makikipag-ugnayan ang team gamit ang email o teleponong ibinigay mo pagkatapos ng pagsusuri.",
        reference: "Reference",
        questions: {
            name: "Maligayang pagdating. Ano ang iyong buong pangalan?",
            email: "Anong email address ang maaaring gamitin ng church para makipag-ugnayan sa iyo?",
            gender: "Paano itatala ang iyong kasarian?",
            birthdate: "Ano ang iyong kaarawan? Makakatulong ito upang matukoy nang tama ang iyong member record.",
            phone: "Anong numero ng telepono ang maaari naming gamitin upang tawagan ka?",
            address_choice: "Nais mo bang idagdag ang iyong kasalukuyang address sa Pilipinas?",
            region_code: "Piliin ang iyong rehiyon.", province_code: "Piliin ang iyong probinsya.",
            city_code: "Piliin ang iyong lungsod o munisipalidad.", barangay_code: "Piliin ang iyong barangay.",
            street_address: "Ilagay ang numero ng bahay, gusali, o kalye."
        },
        labels: {
            name: "Buong pangalan", email: "Email", gender: "Kasarian", birthdate: "Kaarawan", phone: "Telepono", address_choice: "Magdagdag ng address",
            region_code: "Rehiyon", province_code: "Probinsya", city_code: "Lungsod / munisipalidad",
            barangay_code: "Barangay", street_address: "Address sa kalye"
        },
        placeholders: {
            name: "Iyong buong pangalan", email: "ikaw@example.com", phone: "+63 917 123 4567",
            street_address: "Numero ng bahay, gusali, o kalye"
        },
        options: { female: "Babae", male: "Lalaki", yes: "Oo, idagdag ang address", no: "Hindi, laktawan ang address" },
        errors: {
            name: "Ilagay ang iyong pangalan at apelyido gamit ang mga letra lamang.", email: "Pakilagay ang wastong email address.",
            gender: "Pumili ng kasarian.", birthdateRequired: "Pakilagay ang iyong kaarawan.",
            birthdatePast: "Ang kaarawan ay dapat bago ang araw na ito.", phone: "Ang telepono ay dapat 20 character o mas maikli.",
            addressChoice: "Piliin kung magdadagdag ng address.", addressOption: "Pumili mula sa address list.",
            street: "Ang street address ay dapat 255 character o mas maikli."
        }
    }
};

const steps = [
    { key: "name", type: "text", autocomplete: "name", required: true },
    { key: "email", type: "email", autocomplete: "email", required: true },
    { key: "gender", type: "select", required: true, choices: ["female", "male"] },
    { key: "birthdate", type: "date", autocomplete: "bday", required: true },
    { key: "phone", type: "tel", autocomplete: "tel", required: false },
    { key: "address_choice", type: "select", required: true, choices: ["yes", "no"] },
    { key: "region_code", type: "remote-select", required: true, endpoint: () => `${ADDRESS_API}/regions` },
    { key: "province_code", type: "remote-select", required: true, endpoint: answers => `${ADDRESS_API}/provinces/${encodeURIComponent(answers.region_code)}` },
    { key: "city_code", type: "remote-select", required: true, endpoint: answers => `${ADDRESS_API}/cities/${encodeURIComponent(answers.province_code)}` },
    { key: "barangay_code", type: "remote-select", required: true, endpoint: answers => `${ADDRESS_API}/barangays/${encodeURIComponent(answers.city_code)}` },
    { key: "street_address", type: "text", autocomplete: "street-address", required: false }
];

const addressKeys = ["region_code", "province_code", "city_code", "barangay_code", "street_address"];
const state = { language: "en", currentStep: 0, answers: {}, answerLabels: {}, editMode: null, submitted: false, registrationId: null };
const conversation = document.querySelector("#conversation");
const composer = document.querySelector("#composer");
const progressBar = document.querySelector("#progress-bar");
const restartButton = document.querySelector("#restart-button");
const messageTemplate = document.querySelector("#message-template");
const languageButtons = document.querySelectorAll("[data-language]");
const t = () => copy[state.language];

function capitalizeName(value) {
    return value
        .trim()
        .replace(/\s+/g, " ")
        .toLocaleLowerCase(state.language === "fil" ? "fil-PH" : "en-PH")
        .replace(/(^|[\s.'\u2019-])(\p{L})/gu, (_, separator, letter) => `${separator}${letter.toLocaleUpperCase(state.language === "fil" ? "fil-PH" : "en-PH")}`);
}

function applyLanguage() {
    const languageCopy = t();
    document.documentElement.lang = state.language === "fil" ? "fil" : "en";
    document.title = languageCopy.documentTitle;
    document.querySelector("#assistant-name").textContent = languageCopy.assistantName;
    document.querySelector("#assistant-status").textContent = languageCopy.assistantStatus;
    restartButton.textContent = languageCopy.restart;
    languageButtons.forEach(button => button.setAttribute("aria-pressed", String(button.dataset.language === state.language)));
}

function addMessage(text, sender = "assistant", tone = "normal") {
    const fragment = messageTemplate.content.cloneNode(true);
    const row = fragment.querySelector(".message-row");
    const avatar = fragment.querySelector(".message-avatar");
    fragment.querySelector(".message").textContent = text;
    if (sender === "user") { row.classList.add("is-user"); avatar.remove(); }
    if (tone === "error") row.classList.add("is-error");
    conversation.append(fragment);
}

function scrollConversation() { requestAnimationFrame(() => { conversation.scrollTop = conversation.scrollHeight; }); }
function focusAnswerField(field) {
    if (window.matchMedia("(min-width: 821px)").matches) field.focus({ preventScroll: true });
}
function isAddressStep(step) { return addressKeys.includes(step.key); }
function addressEnabled() { return state.answers.address_choice === "yes"; }
function isApplicable(step) { return !isAddressStep(step) || addressEnabled(); }
function activeStepCount() { return addressEnabled() ? steps.length : steps.length - addressKeys.length; }
function completedStepCount() { return steps.slice(0, state.currentStep).filter(isApplicable).length; }

function updateProgress(complete = false) {
    const percent = complete ? 100 : (completedStepCount() / activeStepCount()) * 100;
    progressBar.style.width = `${Math.min(100, Math.max(0, percent))}%`;
}

function formatAnswer(step) {
    const value = state.answers[step.key];
    if (!value) return t().skipped;
    if (state.answerLabels[step.key]) return state.answerLabels[step.key];
    if (step.key === "gender" || step.key === "address_choice") return t().options[value];
    if (step.key === "birthdate") {
        return new Intl.DateTimeFormat(state.language === "fil" ? "fil-PH" : "en-PH", { year: "numeric", month: "long", day: "numeric" })
            .format(new Date(`${value}T00:00:00`));
    }
    return value;
}

function validate(step, value) {
    const errors = t().errors;
    if (step.key === "name") {
        const normalized = capitalizeName(value);
        const nameParts = normalized.split(" ").filter(Boolean);
        return nameParts.length >= 2 && /^[\p{L}\p{M}]+(?:[.'’\-][\p{L}\p{M}]+)*(?: [\p{L}\p{M}]+(?:[.'’\-][\p{L}\p{M}]+)*)+$/u.test(normalized) ? "" : errors.name;
    }
    if (step.key === "email") return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim()) ? "" : errors.email;
    if (step.key === "gender") return ["female", "male"].includes(value) ? "" : errors.gender;
    if (step.key === "birthdate") {
        if (!value) return errors.birthdateRequired;
        return new Date(`${value}T00:00:00`) < new Date() ? "" : errors.birthdatePast;
    }
    if (step.key === "phone") return value.trim().length <= 20 ? "" : errors.phone;
    if (step.key === "address_choice") return ["yes", "no"].includes(value) ? "" : errors.addressChoice;
    if (step.type === "remote-select") return value ? "" : errors.addressOption;
    if (step.key === "street_address") return value.trim().length <= 255 ? "" : errors.street;
    return "";
}

function renderConversation() {
    conversation.replaceChildren();
    for (let index = 0; index < state.currentStep; index += 1) {
        const step = steps[index];
        if (!isApplicable(step) || !(step.key in state.answers)) continue;
        addMessage(t().questions[step.key]);
        addMessage(formatAnswer(step), "user");
    }
    askCurrentQuestion();
}

function askCurrentQuestion() {
    const step = steps[state.currentStep];
    if (!step) { showReview(); return; }
    addMessage(t().questions[step.key]);
    renderAnswerForm(step);
    updateProgress();
    restartButton.hidden = state.currentStep === 0 && Object.keys(state.answers).length === 0;
    scrollConversation();
}

function createField(step) {
    let field;
    if (step.type === "select" || step.type === "remote-select") {
        field = document.createElement("select");
        field.append(new Option(step.type === "remote-select" ? t().loading : t().select, ""));
        (step.choices || []).forEach(value => field.append(new Option(t().options[value], value)));
    } else {
        field = document.createElement("input");
        field.type = step.type;
    }
    field.id = `answer-${step.key}`;
    field.name = step.key;
    field.setAttribute("aria-label", t().questions[step.key]);
    field.required = step.required;
    if (["name", "email"].includes(step.key)) field.maxLength = 255;
    if (step.key === "phone") field.maxLength = 20;
    if (step.key === "street_address") field.maxLength = 255;
    if (step.autocomplete) field.autocomplete = step.autocomplete;
    if (t().placeholders[step.key]) field.placeholder = t().placeholders[step.key];
    if (step.type === "date") field.max = new Date(Date.now() - 86400000).toISOString().slice(0, 10);
    if (step.type === "remote-select") field.disabled = true;
    else field.value = state.answers[step.key] || "";
    return field;
}

function renderAnswerForm(step) {
    composer.replaceChildren();
    const form = document.createElement("form");
    form.className = "answer-form";
    form.noValidate = true;
    const field = createField(step);
    const submit = document.createElement("button");
    submit.className = "primary-button";
    submit.type = "submit";
    submit.textContent = t().continue;
    if (step.type === "remote-select") submit.disabled = true;
    const error = document.createElement("p");
    error.className = "field-error";
    error.setAttribute("role", "alert");
    error.hidden = true;
    if (!step.required) {
        const optional = document.createElement("p");
        optional.className = "optional-label";
        optional.textContent = t().optional;
        form.append(optional);
    }
    form.append(field, submit, error);
    composer.append(form, createComposerActions(step));
    if (step.key === "name") {
        field.addEventListener("input", () => { field.value = capitalizeName(field.value); });
        field.addEventListener("blur", () => { field.value = capitalizeName(field.value); });
    }
    form.addEventListener("submit", event => {
        event.preventDefault();
        const value = step.key === "name" ? capitalizeName(field.value) : field.value.trim();
        if (step.key === "name") field.value = value;
        const validationMessage = validate(step, value);
        if (validationMessage) {
            error.textContent = validationMessage;
            error.hidden = false;
            field.setAttribute("aria-invalid", "true");
            field.focus();
            return;
        }
        const selectedLabel = field.selectedOptions?.[0]?.textContent || "";
        acceptAnswer(step, value, step.type.includes("select") ? selectedLabel : "");
    });
    if (step.type === "remote-select") loadRemoteOptions(step, field, submit, error);
    else requestAnimationFrame(() => focusAnswerField(field));
}

function createComposerActions(step) {
    const actions = document.createElement("div");
    actions.className = "composer-actions";
    if (state.currentStep > 0) {
        const back = document.createElement("button");
        back.className = "text-button";
        back.type = "button";
        back.textContent = t().back;
        back.addEventListener("click", goBack);
        actions.append(back);
    }
    if (!step.required) {
        const skip = document.createElement("button");
        skip.className = "text-button";
        skip.type = "button";
        skip.textContent = t().skip;
        skip.addEventListener("click", () => acceptAnswer(step, "", ""));
        actions.append(skip);
    }
    if (actions.childElementCount === 1) actions.classList.add("composer-actions-start");
    return actions;
}

async function loadRemoteOptions(step, field, submit, error) {
    try {
        const response = await fetch(step.endpoint(state.answers), { headers: { "Accept": "application/json" } });
        if (!response.ok) throw new Error();
        const result = await response.json();
        field.replaceChildren(new Option(t().select, ""));
        result.data.forEach(option => field.append(new Option(option.name, option.code)));
        field.value = state.answers[step.key] || "";
        field.disabled = false;
        submit.disabled = false;
        focusAnswerField(field);
    } catch {
        field.replaceChildren(new Option(t().addressLoadError, ""));
        error.textContent = t().addressLoadError;
        error.hidden = false;
        const retry = document.createElement("button");
        retry.type = "button";
        retry.className = "text-button";
        retry.textContent = t().retry;
        retry.addEventListener("click", () => renderAnswerForm(step));
        composer.append(retry);
    }
}

function clearDependentAddressAnswers(key) {
    const dependencies = {
        region_code: ["province_code", "city_code", "barangay_code"],
        province_code: ["city_code", "barangay_code"], city_code: ["barangay_code"]
    };
    (dependencies[key] || []).forEach(dependent => {
        delete state.answers[dependent];
        delete state.answerLabels[dependent];
    });
}

function acceptAnswer(step, value, label) {
    const changed = state.answers[step.key] !== value;
    if (changed) clearDependentAddressAnswers(step.key);
    state.answers[step.key] = value;
    if (label) state.answerLabels[step.key] = label;
    else delete state.answerLabels[step.key];
    if (step.key === "address_choice" && value === "no") {
        addressKeys.forEach(key => { delete state.answers[key]; delete state.answerLabels[key]; });
        state.currentStep = steps.length;
        showReview();
        return;
    }

    if (state.editMode === "address-choice") {
        if (value === "yes" && !state.answers.region_code) {
            state.editMode = "address";
            state.currentStep += 1;
            renderConversation();
        } else {
            state.editMode = null;
            state.currentStep = steps.length;
            showReview();
        }
        return;
    }

    if (state.editMode === "single" || (state.editMode === "address" && (!changed || !["region_code", "province_code", "city_code"].includes(step.key)))) {
        state.editMode = null;
        state.currentStep = steps.length;
        showReview();
        return;
    }

    state.currentStep += 1;
    if (state.currentStep < steps.length) renderConversation();
    else showReview();
}

function goBack() {
    state.editMode = null;
    let previous = state.currentStep - 1;
    while (previous >= 0 && !isApplicable(steps[previous])) previous -= 1;
    state.currentStep = Math.max(0, previous);
    renderConversation();
}

function showReview() {
    composer.replaceChildren();
    conversation.replaceChildren();
    updateProgress(true);
    addMessage(t().review);
    const card = document.createElement("section");
    card.className = "review-card";
    card.setAttribute("aria-label", t().review);
    const list = document.createElement("dl");
    steps.filter(isApplicable).forEach(step => {
        const item = document.createElement("div");
        const term = document.createElement("dt");
        term.textContent = t().labels[step.key];
        const description = document.createElement("dd");
        description.textContent = formatAnswer(step);
        const edit = document.createElement("button");
        edit.type = "button";
        edit.className = "review-edit";
        edit.textContent = t().edit;
        edit.setAttribute("aria-label", `${t().edit}: ${t().labels[step.key]}`);
        edit.addEventListener("click", () => {
            state.currentStep = steps.indexOf(step);
            state.editMode = step.key === "address_choice" ? "address-choice" : (isAddressStep(step) ? "address" : "single");
            renderConversation();
        });
        item.append(term, description, edit);
        list.append(item);
    });
    card.append(list);
    conversation.append(card);
    renderConsentForm();
    scrollConversation();
}

function renderConsentForm() {
    const form = document.createElement("form");
    form.className = "consent-form";
    const label = document.createElement("label");
    label.className = "consent-label";
    const consent = document.createElement("input");
    consent.type = "checkbox";
    const text = document.createElement("span");
    text.textContent = t().consent;
    label.append(consent, text);
    const error = document.createElement("p");
    error.className = "field-error";
    error.setAttribute("role", "alert");
    error.hidden = true;
    const actions = document.createElement("div");
    actions.className = "submit-actions";
    const edit = document.createElement("button");
    edit.type = "button";
    edit.className = "secondary-button";
    edit.textContent = t().editAnswers;
    edit.addEventListener("click", () => { state.currentStep = 0; state.editMode = null; renderConversation(); });
    const submit = document.createElement("button");
    submit.type = "submit";
    submit.className = "primary-button";
    submit.textContent = t().submit;
    actions.append(edit, submit);
    form.append(label, error, actions);
    composer.append(form);
    form.addEventListener("submit", async event => {
        event.preventDefault();
        if (!consent.checked) {
            error.textContent = t().consentError;
            error.hidden = false;
            consent.focus();
            return;
        }
        await submitRegistration(submit, error);
    });
}

function registrationPayload() {
    const payload = {};
    steps.forEach(step => {
        if (step.key !== "address_choice" && isApplicable(step)) payload[step.key] = state.answers[step.key] || null;
    });
    if (addressEnabled()) {
        payload.address = [
            state.answers.street_address,
            state.answerLabels.barangay_code ? `Brgy. ${state.answerLabels.barangay_code}` : "",
            state.answerLabels.city_code, state.answerLabels.province_code, state.answerLabels.region_code
        ].filter(Boolean).join(", ");
    }
    return payload;
}

async function submitRegistration(button, errorElement) {
    button.disabled = true;
    button.textContent = t().submitting;
    errorElement.hidden = true;
    try {
        const response = await fetch(API_ENDPOINT, {
            method: "POST",
            headers: { "Accept": "application/json", "Content-Type": "application/json" },
            body: JSON.stringify(registrationPayload())
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok) {
            if (response.status === 422 && result.errors) {
                const [field, messages] = Object.entries(result.errors)[0];
                const stepIndex = steps.findIndex(step => step.key === field);
                if (stepIndex >= 0) {
                    state.currentStep = stepIndex;
                    renderConversation();
                    addMessage(messages[0], "assistant", "error");
                    scrollConversation();
                    return;
                }
            }
            throw new Error(result.message || t().submitError);
        }
        state.submitted = true;
        state.registrationId = result.data?.registration_id || null;
        showSuccess();
    } catch (error) {
        errorElement.textContent = error.message || t().networkError;
        errorElement.hidden = false;
        button.disabled = false;
        button.textContent = t().submit;
    }
}

function showSuccess() {
    conversation.replaceChildren();
    composer.replaceChildren();
    updateProgress(true);
    restartButton.hidden = false;
    const card = document.createElement("section");
    card.className = "success-card";
    const title = document.createElement("strong");
    title.textContent = t().successTitle;
    const message = document.createElement("p");
    message.textContent = t().successCopy;
    card.append(title, message);
    if (state.registrationId) {
        const reference = document.createElement("code");
        reference.textContent = `${t().reference}: ${state.registrationId}`;
        card.append(reference);
    }
    conversation.append(card);
}

function restart() {
    state.currentStep = 0;
    state.answers = {};
    state.answerLabels = {};
    state.editMode = null;
    state.submitted = false;
    state.registrationId = null;
    restartButton.hidden = true;
    renderConversation();
}

languageButtons.forEach(button => button.addEventListener("click", () => {
    state.language = button.dataset.language;
    applyLanguage();
    if (state.submitted) showSuccess();
    else if (state.currentStep >= steps.length) showReview();
    else renderConversation();
}));
restartButton.addEventListener("click", restart);
applyLanguage();
renderConversation();
