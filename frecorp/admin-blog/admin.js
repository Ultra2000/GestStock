const articlesKey = 'frecorp_articles';

function loadArticles() {
    const articles = JSON.parse(localStorage.getItem(articlesKey) || '[]');
    const list = document.getElementById('articles-list');
    list.innerHTML = '';
    articles.forEach((article, idx) => {
        const card = document.createElement('div');
        card.className = 'bg-white/5 border border-white/10 rounded-2xl p-6 shadow-lg flex flex-col';
        const imgSrc = article.image && article.image.trim() ? article.image.trim() : 'https://via.placeholder.com/800x400.png?text=FRECORP+Article';
        const safeTitle = article.title || 'Article sans titre';
        const safeContent = article.content || '';
        card.innerHTML = `
            <img src="${imgSrc}" alt="${safeTitle}" class="w-full h-44 object-cover rounded-xl mb-4" loading="lazy">
            <h3 class="text-xl font-semibold mb-2 text-transparent bg-clip-text" style="background-image: linear-gradient(90deg,#3b82f6,#9333ea,#ec4899);">${safeTitle}</h3>
            <p class="text-slate-200 mb-4">${safeContent}</p>
            <div class="flex space-x-2 mt-auto">
                <button class="px-4 py-2 bg-sky-500 hover:bg-sky-600 text-white rounded-lg" onclick="editArticle(${idx})"><i class='fas fa-edit mr-2'></i>Éditer</button>
                <button class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg" onclick="deleteArticle(${idx})"><i class='fas fa-trash mr-2'></i>Supprimer</button>
            </div>
        `;
        list.appendChild(card);
    });
}

function saveArticles(articles) {
    localStorage.setItem(articlesKey, JSON.stringify(articles));
}

function addArticle(e) {
    e.preventDefault();
    const title = document.getElementById('title').value.trim();
    const content = document.getElementById('content').value.trim();
    const image = document.getElementById('image').value.trim();
    if (!title || !content) return;
    const articles = JSON.parse(localStorage.getItem(articlesKey) || '[]');
    articles.push({ title, content, image });
    saveArticles(articles);
    loadArticles();
    e.target.reset();
}

document.getElementById('article-form').addEventListener('submit', addArticle);
window.onload = loadArticles;

function deleteArticle(idx) {
    const articles = JSON.parse(localStorage.getItem(articlesKey) || '[]');
    articles.splice(idx, 1);
    saveArticles(articles);
    loadArticles();
}

function editArticle(idx) {
    const articles = JSON.parse(localStorage.getItem(articlesKey) || '[]');
    const article = articles[idx];
    document.getElementById('title').value = article.title;
    document.getElementById('content').value = article.content;
    document.getElementById('image').value = article.image;
    deleteArticle(idx);
}
