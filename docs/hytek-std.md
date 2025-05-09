### Hy-Tek Time Standards (.std) Field Reference

Hy-Tek Time Standards (`.std`) file follows a semi-documented field structure.

Example line:

`41;P;F;I;0;0;50;1;;27.59;;5;;;;24.19;;`

| Index | Value     | Meaning                                                      |
| ----- | --------- | ------------------------------------------------------------ |
| 0     | `41`      | Event number                                                 |
| 1     | `P`       | Program/Meet code (often `P`, `F`, `S`, etc.)         |
| 2     | `F`       | Gender (`F` = Female, `M` = Male)                            |
| 3     | `I`       | Event type (`I` = Individual, `R` = Relay)                   |
| 4     | `0`       | Age group start                                              |
| 5     | `0`       | Age group end (`0-0` means “open” or all ages)               |
| 6     | `50`      | Distance in yards/meters                                     |
| 7     | `1`       | Stroke code: 1 = Free, 2 = Back, 3 = Breast, 4 = Fly, 5 = IM |
| 8     | *(blank)* | Round code (usually unused)                                  |
| 9     | `27.59`   | **LCM Cut** (or slower standard)                             |
| 10    | *(blank)* | Usually optional or unused                                   |
| 11    | `5`       | Stroke again (redundant in many files) or standard level     |
| 12–14 | *(blank)* | Unused or placeholder fields                                 |
| 15    | `24.19`   | **SCY Cut** (or faster standard)                             |
| 16–17 | *(blank)* | Padding                                                      |

---

⚠️ **Notes:**
- Hy-Tek often uses **field 9 for LCM** and **field 15 for SCY**.
- Field 11 can be used for **standard type** (e.g., 1 = B cut, 5 = A cut).
- Many fields are **reserved or padded** for backward compatibility with older software versions.
