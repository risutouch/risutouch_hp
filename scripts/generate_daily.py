#!/usr/bin/env python3
"""
りすたっち 毎日セリフ生成スクリプト
- nanavi.jp RSSからセンザキッチンの最新ニュースを取得
- りすたっちのInstagram公開ページから最新投稿を取得（可能な場合）
- 曜日・営業情報を踏まえた5件のセリフを生成
- data.json に書き出し、git commit & push
"""

import json
import os
import re
import sys
from datetime import datetime, timezone

try:
    import requests
    from bs4 import BeautifulSoup
except ImportError:
    print("pip install requests beautifulsoup4")
    sys.exit(1)

NANAVI_RSS = "https://nanavi.jp/senzakitchen/feed/"
RISUTOUCH_IG = "https://www.instagram.com/risutouch"

WEEKDAY_JA = ["月", "火", "水", "木", "金", "土", "日"]

SITE_FALLBACKS = [
    {"section": "shops",   "hint": "お取扱店（センザキッチン・えんがわ湯本・すずやっち・ゆずり）の紹介"},
    {"section": "about",   "hint": "手づくり焼き菓子・フィナンシェ・タルトなどの商品紹介"},
    {"section": "contact", "hint": "LINEやInstagramでのお問い合わせ案内"},
    {"section": "faq",     "hint": "よくある質問（予約・アレルギー・ギフトなど）の案内"},
]


SHOP_IMAGES = {
    "センザキッチン": [
        "images/shops/senzakitchen-1.jpg",
        "images/shops/senzakitchen-2.jpg",
        "images/shops/senzakitchen-3.jpg",
    ],
    "えんがわ": [
        "images/shops/engawa-1.jpg",
        "images/shops/engawa-2.jpg",
        "images/shops/engawa-3.jpg",
    ],
    "すずやっち": [
        "images/shops/suzuyatch-1.jpg",
        "images/shops/suzuyatch-2.jpg",
        "images/shops/suzuyatch-3.jpg",
    ],
    "ゆずり": [
        "images/shops/yuzuri-1.jpg",
        "images/shops/yuzuri-2.jpg",
        "images/shops/yuzuri-3.jpg",
    ],
}
RISUTOUCH_IMAGES = ["images/syou.jpg", "images/maker.jpg"]


def select_images(news: list[dict]) -> list[str]:
    import random
    all_text = " ".join(r["title"] for r in news)
    shop_img = None
    for shop, imgs in SHOP_IMAGES.items():
        if shop in all_text:
            shop_img = random.choice(imgs)
            break
    if shop_img is None:
        all_shop_imgs = [i for imgs in SHOP_IMAGES.values() for i in imgs]
        shop_img = random.choice(all_shop_imgs)
    return [shop_img, random.choice(RISUTOUCH_IMAGES)]


def get_recent_news(count: int = 3) -> list[dict]:
    try:
        resp = requests.get(NANAVI_RSS, timeout=10, headers={
            "User-Agent": "Mozilla/5.0 (compatible; risutouch-bot/1.0)"
        })
        resp.raise_for_status()
    except Exception as e:
        print(f"RSS fetch error: {e}")
        return []

    soup = BeautifulSoup(resp.text, "xml")
    results = []

    for item in soup.select("item")[:count]:
        title = item.find("title")
        link  = item.find("link")
        pub   = item.find("pubDate")

        title_text = title.get_text(strip=True) if title else ""
        url_text   = link.get_text(strip=True) if link else ""
        date = None
        if pub:
            try:
                from email.utils import parsedate_to_datetime
                date = parsedate_to_datetime(pub.get_text(strip=True))
            except Exception:
                pass

        if title_text:
            results.append({"title": title_text, "url": url_text, "date": date})

    return results


def get_risutouch_ig_posts() -> list[str]:
    """りすたっちのInstagram公開ページから最新投稿のキャプションを取得する試み"""
    try:
        resp = requests.get(
            f"{RISUTOUCH_IG}/?__a=1&__d=dis",
            timeout=10,
            headers={
                "User-Agent": "Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) "
                              "AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148",
                "Accept": "application/json",
            }
        )
        if resp.status_code == 200:
            data = resp.json()
            edges = (data.get("graphql", {})
                        .get("user", {})
                        .get("edge_owner_to_timeline_media", {})
                        .get("edges", []))
            posts = []
            for e in edges[:5]:
                caption_edges = (e.get("node", {})
                                  .get("edge_media_to_caption", {})
                                  .get("edges", []))
                if caption_edges:
                    text = caption_edges[0].get("node", {}).get("text", "")
                    if text:
                        posts.append(text[:100])
            return posts
    except Exception:
        pass
    return []


def generate_messages(news: list[dict], fallback: dict, today: datetime) -> list[str]:
    weekday = WEEKDAY_JA[today.weekday()]
    is_weekend = today.weekday() >= 5  # 土日

    if news:
        topics = "、".join(r["title"][:25] for r in news[:3])
        news_context = f"センザキッチンの最新情報：{topics}"
    else:
        news_context = f"今日のテーマ：{fallback['hint']}"

    ig_posts = get_risutouch_ig_posts()
    ig_context = ""
    if ig_posts:
        ig_context = f"\nりすたっちInstagramの最新投稿（参考）：{ig_posts[0][:80]}"

    weekend_hint = "週末なので「今週末もよろしく」「土日もどうぞ」系のセリフを1〜2個" if is_weekend else ""

    prompt = f"""山口県長門市の焼き菓子屋「りすたっち」のマスコットキャラクター（りす）として、
今日（{weekday}曜日）にぴったりな短いセリフを5つ生成してください。

条件：
・親しみやすくかわいい口調（語尾に「〜だよ」「〜してね」「〜だね」など）
・絵文字1〜2個OK
・1セリフ30文字以内（長い場合は改行「\\n」で2〜3行に分けてOK）
・{news_context}{ig_context}
・センザキッチンの話題を2〜3個、りすたっちの焼き菓子に関するものを2〜3個
・{weekend_hint}
・臨時休業・連休がある場合は「おやすみです🙏」などを含めてもOK
・特に休業情報がなければ通常営業を自然にアピール

出力形式：JSONの文字列配列のみ。例：
["セリフ1", "セリフ2\\n2行目", "セリフ3", "セリフ4", "セリフ5"]"""

    api_key = os.environ.get("ANTHROPIC_API_KEY")
    if not api_key:
        defaults = {
            "shops":   ["お取扱店、のぞいてみてね🌿", f"今日は{weekday}曜日！おいしいもの探しに来てね🐿",
                        "センザキッチンで販売中だよ🌊", "ゆずり・えんがわ・すずやっちにもあるよ🍪",
                        "りすたっちの焼き菓子、お待ちしてます🌰"],
            "about":   ["旬の素材で丁寧に焼いてます🌰", "フィナンシェ、しっとりおいしいよ🐿",
                        "タルトも人気です🍪", "手づくりにこだわってるよ🌿",
                        "季節の焼き菓子どうぞ🎁"],
            "contact": ["LINEかInstagramで気軽にどうぞ🐿", "ご注文・お問い合わせはこちらへ🌰",
                        "ギフトのご相談もOKだよ🎁", "アレルギーのことも気軽に聞いてね🌿",
                        "お気軽にご連絡ください🍪"],
            "faq":     ["よくある質問も見てみてね🍪", "ご予約・取り置きもできるよ🐿",
                        "アレルギー対応についてもお気軽に🌿", "ギフト対応してるよ🎁",
                        "気になることはなんでも聞いてね🌰"],
        }
        return defaults.get(fallback["section"], ["今日もよろしくね🌰"] * 5)

    try:
        import anthropic
        client = anthropic.Anthropic(api_key=api_key)
        response = client.messages.create(
            model="claude-haiku-4-5-20251001",
            max_tokens=300,
            messages=[{"role": "user", "content": prompt}]
        )
        text = response.content[0].text.strip()
        # JSON配列を抽出
        m = re.search(r'\[.*\]', text, re.DOTALL)
        if m:
            messages = json.loads(m.group())
            if isinstance(messages, list) and len(messages) >= 3:
                return [str(s).strip() for s in messages[:5]]
    except Exception as e:
        print(f"Claude API error: {e}")

    return ["今日もよろしくね🌰", f"{weekday}曜日もどうぞ🐿",
            "センザキッチンで販売中🌊", "手づくり焼き菓子です🍪", "お待ちしてます🌰"]


def main():
    now = datetime.now(timezone.utc)
    # 日本時間で曜日を判定
    from datetime import timedelta
    now_jst = now + timedelta(hours=9)
    today_str = now_jst.strftime("%Y-%m-%d")
    fallback = SITE_FALLBACKS[now_jst.weekday() % len(SITE_FALLBACKS)]

    news = get_recent_news(count=3)
    print(f"取得したニュース: {len(news)}件")

    messages = generate_messages(news, fallback, now_jst)
    print(f"生成セリフ: {messages}")

    if news:
        post_url   = news[0]["url"]
        post_label = " →センザキッチン情報"
    else:
        post_url   = RISUTOUCH_IG
        post_label = " →りすたっちInstagram"

    images = select_images(news)

    data = {
        "date": today_str,
        "messages": messages,
        "post_url": post_url,
        "post_label": post_label,
        "images": images,
    }

    out_path = os.path.join(os.path.dirname(__file__), "..", "data.json")
    with open(out_path, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)

    print(f"✓ {today_str}: {len(messages)}件書き出し完了")


if __name__ == "__main__":
    main()
