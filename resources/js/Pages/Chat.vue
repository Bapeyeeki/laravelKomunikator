<template>
    <div class="container">
      <div class="sidebar">
        <div class="logo">Messenger</div>
        <div class="search-box">
          <input class="search-input" type="text" placeholder="Szukaj czatu..." />
          <div class="search-icon">🔍</div>
        </div>
        <div class="menu">
          <div
            v-for="chan in channels"
            :key="chan"
            :class="{ active: currentChannel === chan }"
            class="channel"
            @click="switchChannel(chan)"
          >
            # {{ chan }}
          </div>
        </div>
        <button class="add-channel" @click="addChannel">+ Dodaj nowy czat</button>
      </div>
  
      <div class="chat-area">
        <div class="chat-header">
          <span># {{ currentChannel }}</span>
        </div>
        <div class="messages">
          <div
            v-for="(msg, index) in messages"
            :key="index"
            :class="['message', msg.username === username ? 'sent' : 'received']"
          >
            <div v-if="msg.username !== username" class="message-header">
              <span class="user">{{ msg.username }}</span>
              <span class="time">{{ formatTime(msg.created_at) }}</span>
            </div>
            <div class="message-content" v-html="msg.message"></div>
          </div>
        </div>
  
        <div class="message-input">
          <input
            v-model="username"
            class="username-input"
            placeholder="Twoja nazwa użytkownika"
            @input="saveUsername"
          />
          
          <!-- Przyciski formatowania -->
          <div class="format-buttons">
            <button 
              class="format-btn" 
              :class="{ active: isBoldActive }"
              @click="toggleBold"
            >
              <i class="fas fa-bold"></i>
            </button>
            <button 
              class="format-btn"
              :class="{ active: isUnderlineActive }" 
              @click="toggleUnderline"
            >
              <i class="fas fa-underline"></i>
            </button>
            <button class="format-btn" @click="toggleEmojiPicker">
              <i class="far fa-smile"></i>
            </button>
          </div>
          
          <div class="input-wrapper">
            <div 
              ref="msgBox" 
              class="input-text" 
              contenteditable="true" 
              placeholder="Napisz wiadomość..." 
              @keydown.enter.prevent="onEnterPress"
            ></div>
            <button class="send-button" @click="sendMessage">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M16.6915026,12.4744748 L3.50612381,13.2599618 C3.19218622,13.2599618 3.03521743,13.4170592 3.03521743,13.5741566 L1.15159189,20.0151496 C0.8376543,20.8006365 0.99,21.89 1.77946707,22.52 C2.41,22.99 3.50612381,23.1 4.13399899,22.8429026 L21.714504,14.0454487 C22.6563168,13.5741566 23.1272231,12.6315722 22.9702544,11.6889879 C22.8132856,11.0605983 22.3423792,10.4322088 21.714504,10.118014 L4.13399899,1.16346272 C3.34915502,0.9 2.40734225,1.00636533 1.77946707,1.4776575 C0.994623095,2.10604706 0.8376543,3.0486314 1.15159189,3.99121575 L3.03521743,10.4322088 C3.03521743,10.5893061 3.34915502,10.7464035 3.50612381,10.7464035 L16.6915026,11.5318905 C16.6915026,11.5318905 17.1624089,11.5318905 17.1624089,12.0031827 C17.1624089,12.4744748 16.6915026,12.4744748 16.6915026,12.4744748 Z" fill="#0084ff"/>
              </svg>
            </button>
          </div>
        </div>
      </div>
      
      <!-- Panel emotek -->
      <div v-if="showEmojiPicker" class="emoji-picker">
        <button 
          v-for="emoji in emojis" 
          :key="emoji" 
          class="emoji-btn" 
          @click="insertEmoji(emoji)"
        >
          {{ emoji }}
        </button>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, onMounted, nextTick } from 'vue';
  import axios from 'axios';
  import Echo from 'laravel-echo';
  import Pusher from 'pusher-js';
  
  const messages = ref([]);
  const currentChannel = ref('general');
  const channels = ref(['general', 'projekty', 'wydarzenia']);
  const username = ref('');
  const msgBox = ref(null);
  
  // Zmienne dla formatowania tekstu
  const isBoldActive = ref(false);
  const isUnderlineActive = ref(false);
  const showEmojiPicker = ref(false);
  
  // Lista emotek
  const emojis = ref(['😊', '😂', '😍', '👍', '🎉', '👋', '❤️', '👌', '🙏', '🔥', '👏', '😎', '🤔', '😉', '🥰', '😁']);
  
  const echo = new Echo({
    broadcaster: 'pusher',
    key: 'your_key',
    cluster: 'eu',
    forceTLS: true,
  });
  
  // Ładowanie wiadomości z API
  const loadMessages = async () => {
    try {
      const res = await axios.get(`http://localhost:8000/api/messages?channel=${currentChannel.value}`);
      messages.value = res.data;
      scrollToBottom();
    } catch (err) {
      console.error("Błąd ładowania wiadomości z bazy:", err);
    }
  };
  
  // Przewinięcie czatu na dół
  const scrollToBottom = () => {
    nextTick(() => {
      const messagesContainer = document.querySelector('.messages');
      if (messagesContainer) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
      }
    });
  };
  
  // Przełączanie pogrubienia tekstu
  const toggleBold = () => {
    isBoldActive.value = !isBoldActive.value;
    applyFormatting('bold');
  };
  
  // Przełączanie podkreślenia tekstu
  const toggleUnderline = () => {
    isUnderlineActive.value = !isUnderlineActive.value;
    applyFormatting('underline');
  };
  
  // Zastosowanie formatowania do zaznaczonego tekstu
  const applyFormatting = (format) => {
    // Musimy użyć document.execCommand, ponieważ pracujemy z polem contenteditable
    msgBox.value.focus();
    document.execCommand(format, false, null);
  };
  
  // Pokazanie/ukrycie panelu emotek
  const toggleEmojiPicker = () => {
    showEmojiPicker.value = !showEmojiPicker.value;
  };
  
  // Wstawianie emotki do tekstu
  const insertEmoji = (emoji) => {
    // Focus na polu wiadomości
    msgBox.value.focus();
    
    // Wstawienie emotki w miejscu kursora
    const selection = window.getSelection();
    if (selection.rangeCount) {
      const range = selection.getRangeAt(0);
      range.deleteContents();
      range.insertNode(document.createTextNode(emoji));
      range.collapse(false);
      selection.removeAllRanges();
      selection.addRange(range);
    } else {
      // Jeśli nie ma zaznaczenia, dodaj na końcu
      msgBox.value.innerHTML += emoji;
    }
    
    // Ukryj panel emotek po wybraniu
    showEmojiPicker.value = false;
  };
  
  // Obsługa klawisza Enter (bez Shift) w polu wiadomości
  const onEnterPress = (e) => {
    if (!e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  };
  
  // Zapisanie nazwy użytkownika w localStorage
  const saveUsername = () => {
    localStorage.setItem('username', username.value);
  };
  
  // Wysłanie wiadomości
  const sendMessage = async () => {
    const message = msgBox.value.innerHTML.trim();
    if (!username.value || !message) return;

    const newMsg = {
      username: username.value,
      message: message,
      created_at: new Date().toISOString(),
      channel: currentChannel.value
    };

    localStorage.setItem('username', username.value);

    // Dodaj lokalnie
    messages.value.push(newMsg);
    
    // Wyślij do API
    try {
      await axios.post('http://localhost:8000/api/messages', newMsg);
    } catch (err) {
      console.error("Błąd wysyłania wiadomości do bazy:", err);
    }

    // Wyczyść pole
    msgBox.value.innerHTML = '';
    
    // Przewiń czat na dół
    scrollToBottom();
  };
  
  // Funkcja zmiany kanału
  const switchChannel = chan => {
    currentChannel.value = chan;
    loadMessages();
  };
  
  // Dodawanie nowego kanału
  const addChannel = () => {
    const newChannel = prompt('Podaj nazwę nowego kanału:');
    if (!newChannel) return;
    
    const channelName = newChannel.trim().toLowerCase();
    if (!channelName || channels.value.includes(channelName)) {
      alert('Ten kanał już istnieje lub ma nieprawidłową nazwę!');
      return;
    }
    
    channels.value.push(channelName);
    currentChannel.value = channelName;
    loadMessages();
  };
  
  // Formatowanie czasu
  const formatTime = time => {
    const date = new Date(time);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  };
  
  onMounted(() => {
    // Wczytaj nazwę użytkownika z localStorage
    const savedUsername = localStorage.getItem('username');
    if (savedUsername) {
      username.value = savedUsername;
    }
    
    // Wczytaj wiadomości
    loadMessages();
  
    // Ustaw nasłuchiwanie na nowe wiadomości
    echo.channel('chat').listen('.new-message', e => {
      if (e.message.channel !== currentChannel.value) return;
      messages.value.push(e.message);
      scrollToBottom();
    });
  });
  </script>
  
  <style scoped>
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Helvetica, Arial, sans-serif;
  }
  
  body {
    background-color: #f0f2f5;
    height: 100vh;
  }
  
  .container {
    display: flex;
    height: 100vh;
    max-width: 100%;
    margin: 0 auto;
    background-color: white;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    position: relative;
  }
  
  /* Sidebar */
  .sidebar {
    width: 280px;
    background-color: #ffffff;
    border-right: 1px solid #e4e6eb;
    display: flex;
    flex-direction: column;
  }
  
  .logo {
    font-size: 24px;
    font-weight: bold;
    padding: 16px;
    color: #0084ff;
    text-align: center;
    border-bottom: 1px solid #e4e6eb;
  }
  
  .search-box {
    padding: 8px 12px;
    margin: 8px;
    position: relative;
  }
  
  .search-input {
    padding: 8px 12px 8px 35px;
    border-radius: 20px;
    border: none;
    background-color: #f0f2f5;
    width: 100%;
    font-size: 14px;
  }
  
  .search-icon {
    position: absolute;
    left: 22px;
    top: 17px;
    color: #65676B;
  }
  
  .menu {
    flex-grow: 1;
    overflow-y: auto;
    padding: 8px 0;
  }
  
  .channel {
    padding: 10px 15px;
    margin: 2px 8px;
    cursor: pointer;
    border-radius: 8px;
    transition: background-color 0.2s;
    font-size: 15px;
    display: flex;
    align-items: center;
  }
  
  .channel:hover {
    background-color: #f5f5f5;
  }
  
  .channel.active {
    background-color: #e6f2ff;
    color: #0084ff;
  }
  
  .add-channel {
    padding: 12px 15px;
    margin: 8px;
    cursor: pointer;
    color: #0084ff;
    font-size: 14px;
    transition: background-color 0.2s;
    border-radius: 8px;
    background: none;
    border: none;
    text-align: left;
    display: flex;
    align-items: center;
  }
  
  .add-channel:hover {
    background-color: #e6f2ff;
  }
  
  /* Chat Area */
  .chat-area {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    background-color: #ffffff;
  }
  
  .chat-header {
    padding: 16px 20px;
    border-bottom: 1px solid #e4e6eb;
    font-weight: bold;
    color: #050505;
    background-color: #fff;
    display: flex;
    align-items: center;
  }
  
  .chat-header span {
    font-size: 16px;
  }
  
  .messages {
    flex-grow: 1;
    padding: 20px;
    overflow-y: auto;
    background-color: #f0f2f5;
    display: flex;
    flex-direction: column;
  }
  
  .message {
    max-width: 70%;
    margin-bottom: 10px;
    padding: 8px 12px;
    border-radius: 18px;
    position: relative;
    animation: fadeIn 0.3s ease-in-out;
    word-wrap: break-word;
    line-height: 1.4;
  }
  
  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
  }
  
  .message.received {
    align-self: flex-start;
    background-color: #f0f0f0;
    border-bottom-left-radius: 4px;
    margin-right: auto;
  }
  
  .message.sent {
    align-self: flex-end;
    background-color: #0084ff;
    color: white;
    border-bottom-right-radius: 4px;
    margin-left: auto;
  }
  
  .message-header {
    display: flex;
    align-items: baseline;
    margin-bottom: 4px;
  }
  
  .user {
    font-weight: bold;
    font-size: 13px;
    margin-right: 6px;
    color: #65676B;
  }
  
  .time {
    font-size: 11px;
    color: #65676B;
    margin-left: 4px;
  }
  
  .message.sent .user {
    display: none;
  }
  
  .message.sent .time {
    color: #BDC7D8;
  }
  
  .message-content {
    font-size: 14px;
    overflow-wrap: break-word;
  }
  
  .message-input {
    padding: 12px 16px;
    border-top: 1px solid #e4e6eb;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    background-color: #ffffff;
  }
  
  .username-input {
    width: 100%;
    padding: 10px 12px;
    margin-bottom: 10px;
    border: 1px solid #e4e6eb;
    border-radius: 4px;
    font-size: 14px;
  }
  
  .format-buttons {
    display: flex;
    gap: 8px;
    margin-right: 10px;
  }
  
  .format-btn {
    padding: 8px;
    background: none;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    transition: background-color 0.2s;
    color: #0084ff;
    height: 36px;
    width: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  .format-btn:hover {
    background-color: #e6f2ff;
  }
  
  .format-btn.active {
    background-color: #e6f2ff;
  }
  
  .input-wrapper {
    flex-grow: 1;
    display: flex;
    align-items: center;
    background-color: #f0f2f5;
    border-radius: 20px;
    padding: 0 4px 0 12px;
  }
  
  .input-text {
    flex-grow: 1;
    min-height: 40px;
    max-height: 120px;
    padding: 10px 8px;
    border: none;
    border-radius: 20px;
    overflow-y: auto;
    background-color: transparent;
    font-size: 14px;
    outline: none;
  }
  
  .input-text[contenteditable=true]:empty:before {
    content: attr(placeholder);
    color: #8a8d91;
  }
  
  .send-button {
    background-color: transparent;
    color: #0084ff;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    transition: background-color 0.2s;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  .send-button svg {
    width: 20px;
    height: 20px;
  }
  
  .send-button:hover {
    background-color: #e6f2ff;
  }
  
  /* Panel emotek */
  .emoji-picker {
    position: absolute;
    bottom: 80px;
    right: 20px;
    background-color: white;
    border-radius: 12px;
    padding: 10px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 100;
    width: 240px;
  }
  
  .emoji-btn {
    font-size: 22px;
    background: none;
    border: none;
    cursor: pointer;
    padding: 6px;
    border-radius: 6px;
    transition: background-color 0.2s;
  }
  
  .emoji-btn:hover {
    background-color: #f0f2f5;
  }
  
  /* Responsive Design */
  @media (max-width: 768px) {
    .container {
      flex-direction: column;
    }
  
    .sidebar {
      width: 100%;
      height: 60px;
      flex-direction: row;
      align-items: center;
      overflow-x: auto;
    }
  
    .logo {
      padding: 10px;
      width: auto;
      border-bottom: none;
      border-right: 1px solid #e4e6eb;
    }
  
    .menu {
      display: flex;
      padding: 0;
      overflow-x: auto;
      flex-grow: 1;
    }
  
    .channel {
      white-space: nowrap;
    }
  
    .chat-area {
      height: calc(100vh - 60px);
    }
  
    .message {
      max-width: 85%;
    }
  }
  </style>