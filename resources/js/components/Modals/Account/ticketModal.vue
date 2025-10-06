<script setup>
import user from "@/app/api/User/user.js";
import { onMounted, ref } from "vue";
import Select from "@/app/select/index.js";
import FunctionsModal from "@/app/modal/functions.js";
import commonOptions from "@/app/modal/options.js";

const ticket = ref({
  title: '',
  description: '',
  category: '',
  exist: false,
  ticket_id: null
});
const messageModel = ref({
  message: '',
  ticket_id: null
});
const messages = ref([]);
const modalFunctions = new FunctionsModal();

onMounted(() => {
  const select = new Select("#selectSupport");
  select._el.addEventListener('itc.select.change', () => {
    ticket.value.category = select.value;
  });
  const { isExist, ticket_id } = user.opened_ticket;
  if (isExist) {
    Object.assign(ticket.value, { exist: true, ticket_id });
    messageModel.value.ticket_id = ticket_id;
    getMessages();
  }
  const interval = setInterval(() => {
    if (modalFunctions.isOpenedSupport()) {
      getMessages();
    }
  }, 5000);
});

async function createTicket() {
  const response = await user.createTicket(ticket.value);
  if (response.status === 200) {
    Object.assign(ticket.value, { exist: true, ticket_id: response.data.ticket_id });
    messageModel.value.ticket_id = response.data.ticket_id;
    await getMessages();
  } else if (response.status === 422) {
    showErrors(response.data.errors);
  }
}

async function getMessages() {
  const response = await user.getMessagesTicket(ticket.value.ticket_id);
  if (response.status === 200) {
    messages.value = response.data.messages;
  } else if (response.status === 422) {
    showErrors(response.data.errors);
  }
}

async function sendMessage() {
  const response = await user.sendMessageTicket(messageModel.value);
  if (response.status === 200) {
    await getMessages();
    messageModel.value.message = '';
  } else if (response.status === 422) {
    showErrors(response.data.errors);
  }
}

function showErrors(errors) {
  for (const key in errors) {
    iziToast.show({
      ...commonOptions,
      message: errors[key][0],
      iconUrl: "/images/fail.svg",
    });
  }
}
</script>

<template>
  <div class="ticket-modal " tabindex="-1" id="tickets-list" aria-modal="true" role="dialog">
    <div class="modal-dialog tickets-modal">
      <div class="modal-content tickets-drop ">
        <div class="tickets-title"><p class="text_17">Tickets</p>
          <div style="cursor: pointer" @click="modalFunctions.closeSupport()" data-bs-dismiss="modal" aria-label="Close">
            <svg width="14" height="14" viewBox="0 0 32 32" fill="#FCFCFD"
                 xmlns="http://www.w3.org/2000/svg">
              <path fill-rule="evenodd" clip-rule="evenodd"
                    d="M7.05849 7.05459C7.57919 6.53389 8.42341 6.53389 8.94411 7.05459L16.0013 14.1118L23.0585 7.05459C23.5792 6.53389 24.4234 6.53389 24.9441 7.05459C25.4648 7.57529 25.4648 8.41951 24.9441 8.94021L17.8869 15.9974L24.9441 23.0546C25.4648 23.5753 25.4648 24.4195 24.9441 24.9402C24.4234 25.4609 23.5792 25.4609 23.0585 24.9402L16.0013 17.883L8.94411 24.9402C8.42341 25.4609 7.57919 25.4609 7.05849 24.9402C6.53779 24.4195 6.53779 23.5753 7.05849 23.0546L14.1157 15.9974L7.05849 8.94021C6.53779 8.41951 6.53779 7.57529 7.05849 7.05459Z"></path>
            </svg>
          </div>
        </div>
        <div v-if="!ticket.exist">

          <div class="tickets_form">
            <label class="text_16 color-gray2" for="selectSupport">Choose category</label>
            <div class="itc-select assets " id="selectSupport">
              <button
                  type="button"

                  style="height: 45px; font-size: 16px"
                  class="itc-select__toggle"
                  data-select="toggle"
                  data-index="-1"
              >
                Category
              </button>
              <div class="itc-select__dropdown" style="min-width: unset">
                <ul class="itc-select__options" style="font-size: 16px">
                  <li
                      class="itc-select__option"
                      data-select="option"
                      data-value="deposit"
                      data-index="1">
                    Deposit
                  </li>
                  <li
                      class="itc-select__option"
                      data-select="option"
                      data-value="withdraw"
                      data-index="2">
                    Withdraw
                  </li>
                  <li
                      class="itc-select__option"
                      data-select="option"
                      data-value="trade"
                      data-index="3">
                    Trade
                  </li>
                  <li
                      class="itc-select__option"
                      data-select="option"
                      data-value="report"
                      data-index="4">
                    Report
                  </li>
                  <li
                      class="itc-select__option"
                      data-select="option"
                      data-value="other"
                      data-index="5">
                    Other
                  </li>
                </ul>
              </div>
            </div>
            <label class="text_16 color-gray2" for="ticket_category">Subject</label>
            <input v-model="ticket.title" class="input" name="subject" id="ticket_subject" type="text"
                   placeholder="The problem in brief">
            <label class="text_16 color-gray2" for="ticket_category">Message</label>
            <textarea v-model="ticket.description" class="input" name="text" id="ticket_text" type="text" rows="4"
                      placeholder="What’s happened?"></textarea>
          </div>

          <button type="button" @click="createTicket" class="small_btn btn-round-full"
                  style="justify-content: center;border: none">
            New ticket
          </button>

        </div>
        <div v-if="ticket.exist" id="chat_container" class="chat-container">
          <div id="message-container" class="message-container">
            <div class="message" v-for="message in messages">
              <p :class="['message-title', { support: message.user_id !== user.id }]">
                {{ message.user_id === user.id ? 'You' : 'Support' }}
              </p>
              <p class="message-text">{{ message.message }}</p>
            </div>

          </div>
          <div class="message-send">
            <div class="message-send-content">
              <input class="input-send_message" v-model="messageModel.message" id="sendMessageInput" type="text" name="message"
                     placeholder="Your message">
              <button type="button" @click="sendMessage" :disabled="messageModel.message.length < 1" class="button-send_message">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="14" viewBox="0 0 13 14" fill="none">
                  <path
                      d="M0 7.657H4.00769V6.34299H0V0.328503C0 0.147077 0.149527 0 0.333974 0C0.390249 0 0.445615 0.0139877 0.494923 0.0406686L12.8269 6.71216C12.9885 6.79954 13.0475 6.99934 12.9586 7.15827C12.9281 7.21286 12.8824 7.2578 12.8269 7.28783L0.494923 13.9593C0.333306 14.0467 0.13023 13.9888 0.0413392 13.8298C0.0142205 13.7813 0 13.7268 0 13.6714V7.657Z"
                      fill="#C4E9FC"/>
                </svg>
              </button>
            </div>
          </div>

        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>

</style>