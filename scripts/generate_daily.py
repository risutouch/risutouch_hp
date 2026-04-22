#!/usr/bin/env python3
"""
りすたっち 毎日セリフ生成スクリプト
- nanavi.jp からセンザキッチンのニュースを取得（7日以内）
- 情報がない場合はサイト各セクションを紹介するセリフを生成
- data.json に書き出し、git commit & push
"""

import json
import os
import re
import sys
from datetime import datetime, timedelta, timezone

try:
    import requests
    from bs4 import BeautifulSoup
except ImportError:
    print("pip install requests beautifulsoup4")
    sys.exit(1)

NANAVI_URL = "https://nanavi.jp/senzakitchen/news/"

SITE_FALLBACKS = [
    {"section": "shops",   "hint": "お取扱店（センザキッチン・えんがわ湯本・すずやっち・ゆずり）の紹介"},
    {"section": "about",   "hint": "手づくり焼き菓子・フィナンシェ・タルトなどの商品紹介"},
    {"section": "contact", "hint": "LINEやInstagramでのお問い合わせ案内"},
    {"section": "faq",     "hint": "よくある質問（予約・アレルギー・ギフトなど）の案内"},
]

DATE_PATTERNS = [
    r"\d{4}/\d{2}/\d{2}",
    r"\d{4}-\d{2}-\d{2}",
    r"\d{4}年\d{1,2}月\d{1,2}日",
]


def parse_date(text: str) -> datetime | None:
    for pat in DATE_PATTERNS:
        m = re.search(pat, text)
        if not m:
            continue
        s = m.group()
        for fmt in ("%Y/%m/%d", "%Y-%m-%d", "%Y年%m月%d日"):
            try:
                return datetime.strptime(s, fmt).replace(tzinfo=timezone.utc)
            except ValueError:
                continue
    return None


def get_recent_news(days: int = 7) -> list[dict]:
    try:
        resp = requests.get(NANAVI_URL, timeout=10, headers={
            "User-Agent": "Mozilla/5.0 (compatible; risutouch-bot/1.0)"
        })
        resp.raise_for_status()
    except Exception as e:
        print(f"fetch error: {e}")
        return []

    soup = BeautifulSoup(resp.text, "html.parser")
    cutoff = datetime.now(timezone.utc) - timedelta(days=days)
    results = []

    for item in soup.select(".news-item, article, .post, .entry"):
        # タイトル
        title_el = item.select_one("a")
        title = title_el.get_text(strip=True) if title_el else ""
        url = title_el.get("href", "") if title_el else ""
        if url and not url.startswith("http"):
            url = "https://nanavi.jp" + url

        # 日付（テキスト全体から正規表現で探す）
        date = None
        for el in item.select("time, .date, span, p"):
            date = parse_date(el.get_text())
            if date:
                break
        if not date:
            date = parse_date(item.get_text())

        if title and (date is None or date >= cutoff):
            results.append({"title": title, "url": url, "date": date})

        if len(results) >= 3:
            break

    # 日付フィルタ（日付が取れた場合のみ）
    dated = [r for r in results if r["date"] is not None]
    if dated:
        results = [r for r in dated if r["date"] >= cutoff]

    return results


def generate_message(news: list[dict], fallback: dict) -> str:
    if news:
        topics = "、".join(r["title"][:20] for r in news)
        context = f"センザキッチンの最新情報：{topics}"
    else:
        context = f"今日のテーマ：{fallback['hint']}"

    # Claude自身がセリフを生成（APIキーなし環境でもroutineなら動く）
    prompt = (
        "山口県長門市の焼き菓子屋「りすたっち」のマスコットキャラクター（りす）として、"
        "ひとこと短いセリフを日本語で生成してください。\n"
        "・親しみやすくかわいい口調\n"
        "・絵文字1〜2個OK\n"
        "・25文字以内\n"
        f"・{context}\n\n"
        "セリフのみ出力してください。"
    )

    api_key = os.environ.get("ANTHROPIC_API_KEY")
    if not api_key:
        # APIキーがない場合はフォールバックメッセージ
        fallback_messages = {
            "shops":   "お取扱店、のぞいてみてね🌿",
            "about":   "旬の素材で焼いてます🌰",
            "contact": "LINEかInstagramで気軽にどうぞ🐿",
            "faq":     "よくある質問も見てみてね🍪",
        }
        return fallback_messages.get(fallback["section"], "今日もよろしくね🌰")

    try:
        import anthropic
        client = anthropic.Anthropic(api_key=api_key)
        response = client.messages.create(
            model="claude-haiku-4-5-20251001",
            max_tokens=80,
            messages=[{"role": "user", "content": prompt}]
        )
        return response.content[0].text.strip()
    except Exception as e:
        print(f"Claude API error: {e}")
        return "今日もよろしくね🌰"


def main():
    today = datetime.now(timezone.utc).strftime("%Y-%m-%d")
    fallback = SITE_FALLBACKS[datetime.now().weekday() % len(SITE_FALLBACKS)]

    news = get_recent_news(days=7)
    print(f"取得したニュース: {len(news)}件")

    message = generate_message(news, fallback)

    data = {
        "date": today,
        "message": message,
        "post_url": news[0]["url"] if news else None,
        "section": None if news else fallback["section"],
    }

    out_path = os.path.join(os.path.dirname(__file__), "..", "data.json")
    with open(out_path, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)

    print(f"✓ {today}: {message}")


if __name__ == "__main__":
    main()
