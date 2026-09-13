"use client";

import { useState } from "react";
import type { SeedFaq } from "@/data/seed";

type FaqAccordionProps = {
  faqs: SeedFaq[];
};

export function FaqAccordion({ faqs }: FaqAccordionProps) {
  const [activeIndex, setActiveIndex] = useState<number | null>(0);

  return (
    <div className="faq-container">
      {faqs.map((faq, index) => {
        const isActive = activeIndex === index;
        return (
          <div
            key={faq.id}
            className={`faq-item${isActive ? " active" : ""}`}
            data-index={index}
          >
            <button
              type="button"
              className="faq-question"
              onClick={() => setActiveIndex(isActive ? null : index)}
              style={{
                width: "100%",
                background: "transparent",
                border: "none",
                textAlign: "left",
              }}
            >
              <h3>{faq.question}</h3>
              <i className="fas fa-chevron-down" />
            </button>
            <div className="faq-answer">
              <p>{faq.answer}</p>
            </div>
          </div>
        );
      })}
    </div>
  );
}
