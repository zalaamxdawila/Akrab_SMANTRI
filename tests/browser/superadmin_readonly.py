"""Authenticated browser smoke for the read-only superadmin pages.

Required environment:
  AKRAB_BROWSER_BASE_URL
  AKRAB_BROWSER_SUPERADMIN_USER
  AKRAB_BROWSER_SUPERADMIN_PASSWORD

Remote execution additionally requires AKRAB_BROWSER_ALLOW_REMOTE=true.
"""

import json
import os
import tempfile
from urllib.parse import urlparse

from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By


def required(name: str) -> str:
    value = os.environ.get(name, "").strip()
    if not value:
        raise RuntimeError(f"Missing protected browser setting: {name}")
    return value


base_url = required("AKRAB_BROWSER_BASE_URL").rstrip("/") + "/"
username = required("AKRAB_BROWSER_SUPERADMIN_USER")
password = required("AKRAB_BROWSER_SUPERADMIN_PASSWORD")
host = urlparse(base_url).hostname
if (
    host not in {"127.0.0.1", "localhost", "akrab.portodq.com"}
    or (
        host not in {"127.0.0.1", "localhost"}
        and os.environ.get("AKRAB_BROWSER_ALLOW_REMOTE") != "true"
    )
):
    raise RuntimeError("Browser target is not an approved AKRAB environment.")

with tempfile.TemporaryDirectory(prefix="akrab-superadmin-browser-") as profile:
    options = Options()
    options.add_argument("--headless=new")
    options.add_argument("--disable-gpu")
    options.add_argument("--no-first-run")
    options.add_argument(f"--user-data-dir={profile}")
    options.add_argument("--window-size=1440,1000")
    options.set_capability(
        "goog:loggingPrefs",
        {"browser": "ALL", "performance": "ALL"},
    )
    driver = webdriver.Chrome(options=options)
    try:
        driver.get(base_url + "login.php")
        driver.find_element(By.NAME, "username").send_keys(username)
        driver.find_element(By.NAME, "password").send_keys(password)
        driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()

        results = []
        for route in [
            "superadmin/dashboard.php",
            "superadmin/users.php",
            "superadmin/audit.php",
        ]:
            driver.get(base_url + route)
            results.append(
                {
                    "route": route,
                    "title": driver.title,
                    "main": len(
                        driver.find_elements(By.CSS_SELECTOR, "main#main-content")
                    ),
                    "h1": len(driver.find_elements(By.CSS_SELECTOR, "h1")),
                    "overflow": driver.execute_script(
                        "return document.documentElement.scrollWidth > "
                        "document.documentElement.clientWidth;"
                    ),
                }
            )

        severe = [
            entry
            for entry in driver.get_log("browser")
            if entry["level"] in {"SEVERE", "WARNING"}
        ]
        print(json.dumps({"pages": results, "browser_logs": severe}, indent=2))
        assert all(page["main"] == 1 for page in results)
        assert all(page["h1"] == 1 for page in results)
        assert all(page["overflow"] is False for page in results)
        assert severe == []
        assert "password_hash" not in driver.page_source
    finally:
        password = ""
        driver.quit()
