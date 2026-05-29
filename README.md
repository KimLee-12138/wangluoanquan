# IoT 固件漏洞分析实验环境

[![CVE](https://img.shields.io/badge/CVE-2019--17621-red)](https://nvd.nist.gov/vuln/detail/CVE-2019-17621)
[![Target](https://img.shields.io/badge/target-D--Link%20DIR--859-blue)]()
[![Arch](https://img.shields.io/badge/arch-MIPS32%20BE%20%7C%20uClibc-lightgrey)]()
[![Phase](https://img.shields.io/badge/phase-3%2F3%20complete-brightgreen)]()

本仓库是对 **CVE-2019-17621**（D-Link DIR-859 `gena.cgi` UPnP GENA SUBSCRIBE Callback 命令注入漏洞）的完整复现、分析与自动化工具构建实验环境。

> ⚠️ **安全声明**：所有分析仅针对本地固件文件。未对任何真实设备、公网 IP 或校园网络进行测试。所有仿真验证在 QEMU/FirmAE 隔离虚拟环境中进行。

---

## 仓库结构

```
iot-lab/
├── README.md                        # 本文件
├── FirmAE/                          # FirmAE 全系统仿真框架
├── cve-2019-17621/                  # 初版项目（阶段一基础工作）
└── cve-2019-17621-v2/               # ★ 完整项目（阶段一至阶段三）
    ├── README.md                    #   项目详细 README
    ├── 阶段一漏洞复现报告.md           #   阶段一：漏洞复现（668 行）
    ├── 阶段三总结报告.md               #   阶段三：总结报告（650 行）
    ├── firmware/                    #   固件文件
    │   └── DIR859Ax_FW105b03.bin   #     D-Link DIR-859 FW105b03
    ├── extract/                     #   解包后的 rootfs
    ├── phase2/                      #   阶段二：漏洞挖掘
    │   ├── 阶段二漏洞挖掘过程报告.md    #     挖掘方法论（928 行）
    │   └── 阶段二静态分析记录.md        #     静态分析（665 行）
    └── phase3/                      #   阶段三：FirmHound Skill ★
        ├── 自动化流程说明文档.md        #     用户手册（860 行）
        ├── final-skill/             #     FirmHound 完整源码
        │   └── iot-firmware-vuln-workflow/
        │       ├── scripts/         #       6 个可执行脚本
        │       ├── references/      #       9 个参考文档
        │       └── examples/        #       示例配置
        ├── test-run/output/         #     自动化试运行输出
        ├── logs/                    #     运行日志
        └── notes/                   #     分析笔记
```

---

## 项目总览

```
CVE-2019-17621 完整分析项目
│
├── 阶段一：漏洞复现与分析
│   ├── 固件信息采集与解包（DLOB 加密格式）
│   ├── 静态分析（认证边界、危险函数、数据流）
│   ├── QEMU/FirmAE 本地仿真验证
│   └── 产出：漏洞复现报告 + 初学者复现指南
│
├── 阶段二：漏洞挖掘过程
│   ├── 攻击面枚举方法论
│   ├── 协议级 Fuzzing 计划
│   ├── Skill 原型设计（10 维评分 → 8 维评分）
│   └── 产出：挖掘过程报告 + 静态分析记录
│
└── 阶段三：FirmHound Skill 构建 ★
    ├── 6 个自动化脚本（4 Shell + 2 Python，~4,270 行）
    ├── 9 个参考文档（~3,930 行）
    ├── 10 步自动化流水线
    ├── 8 维风险评分体系（P-I-D-C-F-S-V-E，满分 23）
    ├── 自动化试运行（D-Link DIR-859 验证）
    └── 产出：FirmHound Skill + 自动化流程说明 + 总结报告
```

---

## 漏洞概述

| 属性 | 值 |
|------|-----|
| CVE 编号 | CVE-2019-17621 |
| 受影响设备 | D-Link DIR-859 |
| 固件版本 | FW105b03（及更早版本） |
| CPU 架构 | MIPS32 Big Endian, uClibc 0.9.33.2 |
| 漏洞类型 | 命令注入（Command Injection） |
| 漏洞入口 | `gena.cgi` — UPnP GENA SUBSCRIBE Callback 参数 |
| 认证状态 | **预认证**（Preauth）— 无需登录即可触发 |
| CVSS 评分 | 9.8 (Critical) |

### 漏洞原理

D-Link DIR-859 的 UPnP 实现中，`gena.cgi` handler 将 GENA SUBSCRIBE 请求的 Callback URL 参数**未经过滤直接拼接到 `system()` 调用**中：

```
HTTP SUBSCRIBE /gena.cgi
Callback: <http://attacker/>; malicious_command;
                    ↑
                    拼接到 system("xmldb ... Callback=<用户输入>")
                    → 命令注入
```

---

## 阶段三：FirmHound Skill ★

**FirmHound** 是本项目核心产出 —— 一个纯本地的 IoT 固件漏洞自动化发现工具，可在约 2 分钟内完成从固件文件到漏洞分析报告的全流程。

> 🔗 FirmHound 独立仓库：[KimLee-12138/Firmhound.skill](https://github.com/KimLee-12138/Firmhound.skill)

### 6 大核心模块

| # | 模块 | 实现 |
|---|------|------|
| 1 | 固件解包 | 三层回退（binwalk → dd+unsquashfs → sasquatch），支持 DLOB/uImage/SquashFS |
| 2 | Web 入口识别 | 13 目录扫描，CGI/PHP/Lua/UPnP 入口枚举，handler 函数提取 |
| 3 | 认证边界分析 | HTTP route → C handler → PHP 三层交叉验证 |
| 4 | 危险函数扫描 | C ELF 导入表 + PHP 源码匹配，preauth × danger 交叉引用 |
| 5 | 风险评分排序 | 8 维 P-I-D-C-F-S-V-E 量化评分（满分 23），自动分类排序 |
| 6 | 自动报告生成 | 16 节结构化 Markdown 报告，[AUTO]/[AGENT] 智能标记 |

### 自动化试运行结果（D-Link DIR-859 FW105b03）

| 步骤 | 脚本 | 结果 |
|------|------|------|
| 01 | `collect_firmware_info.sh` | ✅ HASH/magic/binwalk 全部正确 |
| 02 | `unpack_firmware.sh` | ⚠️ sasquatch 限制 → 自动回退到预解包 rootfs |
| 05 | `scan_web_entries.sh` | ✅ 16 个输出文件，918 PHP，20 cgibin handler |
| 06-07 | `scan_dangerous_patterns.sh` | ✅ 28 system() / 21 popen() 导入 |
| 08 | `rank_candidates.py` | ✅ 4 个 CRITICAL |
| 10 | `generate_report_skeleton.py` | ✅ 459 行 16 节报告 |

### Top 4 风险候选

| 排名 | Handler | 分数 | 风险 | 预认证 |
|------|---------|------|------|--------|
| 1 | genacgi_main | 20/23 | 🔴 CRITICAL | 是 |
| 2 | hnap_main | 19/23 | 🔴 CRITICAL | 部分 |
| 3 | soapcgi_main | 19/23 | 🔴 CRITICAL | 是 |
| 4 | ssdpcgi_main | 19/23 | 🔴 CRITICAL | 是 |

### 8 维风险评分

| 维度 | 缩写 | 范围 | 含义 |
|------|------|------|------|
| Preauth | P | 0–3 | 是否无需认证即可访问 |
| Input Control | I | 0–3 | 攻击者控制输入的程度 |
| Dangerous Func | D | 0–3 | 危险函数（system/popen/exec）数量 |
| Complexity | C | 0–3 | 数据流路径复杂度 |
| File Write | F | 0–3 | 是否存在文件写入路径 |
| Shell Injection | S | 1–4 | 命令注入可行性 |
| Validation | V | 0–2 | 输入验证强度（越低越危险） |
| Exploitability | E | 0–2 | 利用前置条件（越低越容易） |

**阈值**：LOW 0-5 / MEDIUM 6-10 / HIGH 11-16 / CRITICAL ≥17

---

## 安全边界（8 条强制规则）

| # | 规则 |
|---|------|
| R1 | 仅分析本地固件/rootfs/日志 |
| R2 | 仅 QEMU/FirmAE 隔离环境仿真 |
| R3 | 禁止公网 IP、真实路由器、校园网设备 |
| R4 | 禁止反弹 shell、持久化、telnet、下载执行 |
| R5 | 验证命令仅 `id`/`echo`/`touch /tmp/lab_marker*` |
| R6 | 无法确认时停止并要求人工确认 |
| R7 | 不编造实验结果 |
| R8 | 不完整部分标记"待完成" |

---

## 阶段三主要交付物

| 交付物 | 路径 | 规模 |
|--------|------|------|
| FirmHound Skill 源码 | `cve-2019-17621-v2/phase3/final-skill/iot-firmware-vuln-workflow/` | 6 脚本 + 9 参考 + 1 配置 |
| 自动化流程说明文档 | `cve-2019-17621-v2/phase3/自动化流程说明文档.md` | 860 行 |
| 自动化试运行日志 | `cve-2019-17621-v2/phase3/logs/automated_workflow_run.log` | 866 行 |
| 自动生成分析报告 | `cve-2019-17621-v2/phase3/test-run/output/自动生成的漏洞分析报告.md` | 459 行 |
| 本地验证截图清单 | `cve-2019-17621-v2/phase3/notes/local_validation_screenshot_checklist.md` | 771 行 |
| 脚本测试报告 | `cve-2019-17621-v2/phase3/notes/skill_script_test_summary.md` | 221 行 |
| 阶段三总结报告 | `cve-2019-17621-v2/阶段三总结报告.md` | 650 行 |

---

## 快速导航

| 想了解什么 | 看哪个文件 |
|-----------|-----------|
| 漏洞原理与复现步骤 | `cve-2019-17621-v2/阶段一漏洞复现报告.md` |
| 漏洞挖掘方法论 | `cve-2019-17621-v2/phase2/阶段二漏洞挖掘过程报告.md` |
| FirmHound Skill 使用方法 | `cve-2019-17621-v2/phase3/自动化流程说明文档.md` |
| FirmHound 独立仓库 | [Firmhound.skill](https://github.com/KimLee-12138/Firmhound.skill) |
| 自动化试运行结果 | `cve-2019-17621-v2/phase3/logs/automated_workflow_run.log` |
| 最终分析报告（样例） | `cve-2019-17621-v2/phase3/test-run/output/自动生成的漏洞分析报告.md` |
| 阶段三交付物总览 | `cve-2019-17621-v2/阶段三总结报告.md` |

---

## 环境依赖

| 工具 | 用途 | 阶段 |
|------|------|------|
| `bash` 4.0+ | Shell 脚本运行 | 全部 |
| `python3` 3.8+ | Python 脚本运行 | 阶段三 |
| `binwalk` | 固件结构分析 | 全部 |
| `sasquatch` | SquashFS LZMA 解包 | 阶段一/三 |
| `unsquashfs` | SquashFS 标准解包 | 阶段一/三 |
| `file` / `strings` / `xxd` | 二进制分析 | 全部 |
| `readelf` (binutils) | ELF 安全特性 | 阶段三 |
| QEMU (qemu-mips-static) | MIPS 仿真 | 阶段一/二 |
| FirmAE | 全系统仿真 | 阶段一/二 |

---

## 参考链接

- [CVE-2019-17621 — NVD](https://nvd.nist.gov/vuln/detail/CVE-2019-17621)
- [FirmHound Skill 独立仓库](https://github.com/KimLee-12138/Firmhound.skill)
- [Binwalk](https://github.com/ReFirmLabs/binwalk)
- [sasquatch](https://github.com/devttys0/sasquatch)
- [FirmAE](https://github.com/pr0v3rbs/FirmAE)

---

## 许可与免责

本项目仅供**教育、研究和授权安全审计**目的使用。作者不对滥用行为承担任何责任。
