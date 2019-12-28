# Telegram MTG Chatbot

An inline chatbot to find pictures and information about Magic: The Gathering cards, developed with Laravel Framework using [scfryfall's API](https://scryfall.com/) to search for results

## How to use

This bot's usage is as simple as any telegram inline query, just go to any chat, type the bot's username followed by a query, you will see the first 50 results you would see on scryfall's website, pick one and send it, the sent image will include a button with a link to the card's gatherer page

There is support for the full scryfall syntax for querys, you are not limited to card names, go to [scryfall's search reference](https://scryfall.com/docs/syntax) for more information

## TO DO's

- Extract all scryfall's API related code to a new class and load it using dependency injection
- Extract all telegram's API related code to a new class and load it using dependency injection
- Write unit tests


## Ideas for future implementations

- Show card prices from diferent stores (this information is already available on scryfall)
- Receive a list of cards and send a pdf file for printing of proxies
- Connect with some deckbuilder service (mtggolfish.com, tappedout.net, deckbox.org) to show deck statistics and share deck links without leaving telegram
