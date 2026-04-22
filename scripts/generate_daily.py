#!/usr/bin/env python3
"""
りすたっち 毎日セリフ生成スクリプト
- InstagramのInstagram投稿（7日以内）を取得してセリフを生成
- 投稿がない場合はサイト各セクションを紹介するセリフを生成
- data.json に書き出す
"""

import json
import os
import sys
from datetime import datetime, timedelta, timezone

try:
    import instaloader
    INSTALOADER_AVAILABLE = True
except ImportError:
    INSTALOADER_AVAILABLE = False
    print("instaloader not found, skipping Instagram fetch")

try:
    import anthropic
except ImportError:
    print("anthropic package required: pip install anthropic")
    sys.exit(1)

INSTAGRAM_USER = "risutouch"

SITE_FALLBACKS = [
    {"section": "shops",   "hint": "お取扱店（センザキッチン・えんがわ湯本など）の紹介"},
    {"section": "about",   "hint": "手づくり焼き菓子・フィナンシェ・タルトなどの商品紹介"},
    {"section": "contact", "hint": "LINEやInstagramでのお問い合わせ案内"},
    {"section": "faq",     "hint": "よくある質問（予約・アレルギー・ギフトなど）の案内"},
]


def get_recent_posts(username: str, days: int = 7) -> list[dict]:
    if not INSTALOADER_AVAILABLE:
        return []
    try:
        L = instaloader.Instaloader(quiet=True, download_pictures=False)
        profile = instaloader.Profile.from_username(L.context, username)
        cutoff = datetime.now(timezone.utc) - timedelta(days=days)
        posts = []
        for post in profile.get_posts():
            post_date = post.date_utc.replace(tzinfo=timezone.utc)
            if post_date < cutoff:
                break
            posts.append({
                "caption": (post.caption or "")[:200],
                "url": f"https://www.instagram.com/p/{post.shortcode}/",
            })
            if len(posts) >= 5:
                break
        return posts
    except Exception as e:
        print(f"Instagram fetch error: {e}")
        return []


def generate_message(posts: list[dict], fallback: dict) -> str:
    api_key = os.environ.get("ANTHROPIC_API_KEY")
    if not api_key:
        return "今日もよろしくね🌰"

    client = anthropic.Anthropic(api_key=api_key)

    if posts:
        captions = "\n".join(f"・{p['caption']}" for p in posts if p["caption"])
        context = f"最近のInstagram投稿（7日以内）：\n{captions}"
    else:
        context = f"今日のテーマ：{fallback['hint']}"

    response = client.messages.create(
        model="claude-haiku-4-5-20251001",
        max_tokens=80,
        messages=[{
            "role": "user",
            "content": (
                "山口県長門市の焼き菓子屋「りすたっち」のマスコットキャラクター（りす）として、"
                "ひとこと短いセリフを日本語で生成してください。\n"
                "・親しみやすくかわいい口調\n"
                "・絵文字1〜2個OK\n"
                "・25文字以内\n"
                f"・{context}\n\n"
                "セリフのみ出力してください。"
            )
        }]
    )
    return response.content[0].text.strip()


def main():
    today = datetime.now(timezone.utc).strftime("%Y-%m-%d")

    posts = get_recent_posts(INSTAGRAM_USER, days=7)

    # 曜日でフォールバックをローテーション
    fallback = SITE_FALLBACKS[datetime.now().weekday() % len(SITE_FALLBACKS)]

    message = generate_message(posts, fallback)

    data = {
        "date": today,
        "message": message,
        "post_url": posts[0]["url"] if posts else None,
        "section": None if posts else fallback["section"],
    }

    out_path = os.path.join(os.path.dirname(__file__), "..", "data.json")
    with open(out_path, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)

    print(f"✓ {today}: {message}")


if __name__ == "__main__":
    main()
